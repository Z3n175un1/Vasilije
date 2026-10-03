<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TipoGasto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El CLASIFICADOR DE GASTOS es el catalogo maestro que sustituye a los
 * <select> con literales que cada vista repetia.
 *
 * Lo que se verifica aqui es la REGLA DE ALCANCE, que es el requisito
 * funcional: al registrar un gasto de UNIDAD solo se ofrecen los
 * clasificadores con afecta_unidad, y al registrar un gasto general solo
 * los de afecta_general.
 *
 * @uses \App\Services\ClasificadorGastoService
 */
class ClasificadorGastoTest extends TestCase
{
    use RefreshDatabase;

    /** Atajo semantico: el helper generico vive en Tests\TestCase. */
    private function nuevo(array $atributos = []): int
    {
        return $this->clasificador($atributos);
    }

    // =================================================================
    // CODIGO CORRELATIVO
    //
    // El código no lo escribe el operador: se asigna solo y en orden.
    // Si se pudiera editar, dos clasificadores podrian compartir código y
    // los gastos ya registrados quedarian apuntando a una fila distinta.
    // =================================================================

    #[Test]
    public function el_codigo_se_asigna_solo_y_correlativo(): void
    {
        $primero = $this->nuevo(['descripcion' => 'Primero']);
        $segundo = $this->nuevo(['descripcion' => 'Segundo']);
        $tercero = $this->nuevo(['descripcion' => 'Tercero']);

        $codigos = DB::table('global.clasificador_gastos')
            ->whereIn('id_clasificador', [$primero, $segundo, $tercero])
            ->orderBy('id_clasificador')
            ->pluck('codigo')
            ->all();

        $this->assertCount(3, $codigos);

        foreach ($codigos as $codigo) {
            $this->assertMatchesRegularExpression('/^CG-\d{4}$/', $codigo);
        }

        // Correlativo y sin repetir.
        $this->assertSame(count($codigos), count(array_unique($codigos)));

        $numeros = array_map(static fn ($c) => (int) substr($c, 3), $codigos);
        $this->assertSame($numeros[0] + 1, $numeros[1]);
        $this->assertSame($numeros[1] + 1, $numeros[2]);
    }

    #[Test]
    public function el_codigo_no_se_puede_forzar_desde_la_peticion(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/clasificadores', [
            'descripcion' => 'Con codigo inventado',
            'tipo_gasto' => TipoGasto::Combustible->value,
            'afecta_unidad' => true,
            'afecta_general' => false,
            'codigo' => 'CG-9999',
        ]);

        $codigo = DB::table('global.clasificador_gastos')
            ->where('descripcion', 'Con codigo inventado')
            ->value('codigo');

        $this->assertNotSame('CG-9999', $codigo, 'El codigo debe seguir la correlatividad, no lo que mande el formulario.');
        $this->assertMatchesRegularExpression('/^CG-\d{4}$/', (string) $codigo);
    }

    #[Test]
    public function el_formulario_muestra_el_proximo_codigo_sin_dejar_editarlo(): void
    {
        $this->nuevo(['descripcion' => 'Para calcular el siguiente']);

        $contenido = $this->actingAs($this->usuarioConRol('admin'))
            ->get('/clasificadores/nuevo')
            ->assertOk()
            ->getContent();

        // Se anuncia el codigo que le tocara...
        $siguiente = app(\App\Services\ClasificadorGastoService::class)->siguienteCodigo();
        $this->assertStringContainsString($siguiente, $contenido);

        // ...pero el campo no acepta escritura: se busca el <input> que lleva el
        // valor y se comprueba que tenga `readonly` en el mismo elemento.
        $campo = $this->inputQueMuestra($contenido, $siguiente);

        $this->assertNotNull($campo, 'No se encontro el campo que muestra el codigo sugerido.');
        $this->assertMatchesRegularExpression(
            '/\breadonly\b/i',
            $campo,
            'El campo de código debe mostrarse en solo lectura.'
        );

        $this->assertStringNotContainsString('placeholder="CG-', $contenido);
    }

    /**
 * Devuelve el elemento <input> que tiene el valor dado.
 *
 * Blade rompe los atributos en varias lineas, asi que el patrón tiene que
 * tolerar saltos de linea entreTags.
 */
    private function inputQueMuestra(string $html, string $valor): ?string
    {
        if (preg_match_all('/<input\b[^>]*>/is', $html, $coincidencias) === false) {
            return null;
        }

        foreach ($coincidencias[0] as $input) {
            if (str_contains($input, 'value="' . $valor . '"')) {
                return $input;
            }
        }

        return null;
    }

    #[Test]
    public function el_codigo_de_un_clasificador_existente_tampoco_se_edita(): void
    {
        $id = $this->nuevo(['descripcion' => 'Original']);

        $codigoOriginal = DB::table('global.clasificador_gastos')
            ->where('id_clasificador', $id)
            ->value('codigo');

        $this->actingAs($this->usuarioConRol('admin'))->put("/clasificadores/{$id}", [
            'descripcion' => 'Renombrado',
            'tipo_gasto' => TipoGasto::Combustible->value,
            'afecta_unidad' => true,
            'afecta_general' => false,
            'codigo' => 'CG-8888',
        ]);

        $codigoFinal = DB::table('global.clasificador_gastos')
            ->where('id_clasificador', $id)
            ->value('codigo');

        $this->assertSame($codigoOriginal, $codigoFinal, 'Cambiar el codigo dejaria huerfanos los gastos que lo referencian.');
    }

    // =================================================================
    // ALCANCE
    // =================================================================

    #[Test]
    public function el_filtro_por_alcance_devuelve_solo_lo_correspondiente(): void
    {
        $soloUnidad = $this->nuevo([
            'descripcion' => 'Solo unidad A',
            'afecta_unidad' => true,
            'afecta_general' => false,
        ]);

        $soloGeneral = $this->nuevo([
            'descripcion' => 'Solo general B',
            'afecta_unidad' => false,
            'afecta_general' => true,
        ]);

        $ambos = $this->nuevo([
            'descripcion' => 'Ambos C',
            'afecta_unidad' => true,
            'afecta_general' => true,
        ]);

        $inactivo = $this->nuevo([
            'descripcion' => 'Inactivo D',
            'afecta_unidad' => true,
            'afecta_general' => true,
            'estado' => 'INACTIVO',
        ]);

        $deUnidad = app(\App\Services\ClasificadorGastoService::class)->listar('unidad');
        $idsUnidad = $deUnidad->pluck('id_clasificador')->all();

        $this->assertContains($soloUnidad, $idsUnidad);
        $this->assertContains($ambos, $idsUnidad);
        $this->assertNotContains($soloGeneral, $idsUnidad, 'Un clasificador general no debe ofrecerse para unidad.');
        $this->assertNotContains($inactivo, $idsUnidad, 'Un inactivo no debe ofrecerse.');

        $deGeneral = app(\App\Services\ClasificadorGastoService::class)->listar('general');
        $idsGeneral = $deGeneral->pluck('id_clasificador')->all();

        $this->assertContains($soloGeneral, $idsGeneral);
        $this->assertContains($ambos, $idsGeneral);
        $this->assertNotContains($soloUnidad, $idsGeneral);

        $todos = app(\App\Services\ClasificadorGastoService::class)->listar('todos');
        $this->assertGreaterThanOrEqual(4, $todos->count());
    }

    #[Test]
    public function el_endpoint_api_expone_el_alcance_solicitado(): void
    {
        $this->nuevo(['descripcion' => 'Unidad X', 'afecta_unidad' => true, 'afecta_general' => false]);
        $this->nuevo(['descripcion' => 'General Y', 'afecta_unidad' => false, 'afecta_general' => true]);

        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->getJson('/api/clasificadores?alcance=unidad')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('alcance', 'unidad')
            ->assertJsonMissing(['descripcion' => 'General Y']);

        $this->actingAs($admin)->getJson('/api/clasificadores?alcance=general')
            ->assertOk()
            ->assertJsonMissing(['descripcion' => 'Unidad X']);
    }

    #[Test]
    public function un_alcance_invalido_cae_en_todos_en_vez_de_error(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->getJson('/api/clasificadores?alcance=inventado')
            ->assertOk()
            ->assertJsonPath('alcance', 'todos');
    }

    // =================================================================
    // VALIDACION DE ALCANCE EN EL SERVIDOR
    // =================================================================

    #[Test]
    public function el_servidor_rechaza_un_clasificador_de_alcance_equivocado(): void
    {
        $general = $this->nuevo([
            'descripcion' => 'Alcance general',
            'tipo_gasto' => TipoGasto::Impuestos->value,
            'afecta_unidad' => false,
            'afecta_general' => true,
        ]);

        $vehiculo = $this->vehiculo();
        $banco = $this->banco();

        // Un clasificador de alcance general NO puede usarse en un gasto de
        // unidad. El filtro del <select> es solo comodidad: la barrera real
        // esta en el servidor.
        $this->actingAs($this->usuarioConRol('admin'))
            ->post('/gastos', [
                'id_vehiculo' => $vehiculo,
                'id_clasificador' => $general,
                'monto' => 100,
                'fecha_gasto' => now()->format('Y-m-d'),
                'concepto' => 'Intento con alcance equivocado',
                'condicion_pago' => 'CONTADO',
                'id_banco' => $banco,
            ])
            ->assertSessionHasErrors('id_clasificador');

        $this->assertDatabaseCount('global.gastos', 0);
    }

    // =================================================================
    // CRUD
    // =================================================================

    #[Test]
    public function crea_un_clasificador_con_codigo_autogenerado(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/clasificadores', [
            'descripcion' => 'Servicio de grua',
            'tipo_gasto' => TipoGasto::Mantenimiento->value,
            'afecta_unidad' => '1',
        ])->assertRedirect('/clasificadores')->assertSessionHas('success');

        $fila = DB::table('global.clasificador_gastos')->where('descripcion', 'Servicio de grua')->first();

        $this->assertNotNull($fila);
        $this->assertStringStartsWith('CG-', $fila->codigo);
        $this->assertSame(TipoGasto::Mantenimiento->value, $fila->tipo_gasto);
        $this->assertTrue((bool) $fila->afecta_unidad);
        $this->assertFalse((bool) $fila->afecta_general, 'Sin marcar, el alcance general debe quedar en falso.');
    }

    #[Test]
    public function exige_al_menos_un_alcance(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/clasificadores', [
            'descripcion' => 'Sin alcance',
            'tipo_gasto' => TipoGasto::Varios->value,
        ])->assertSessionHasErrors('afecta_general');

        $this->assertDatabaseMissing('global.clasificador_gastos', ['descripcion' => 'Sin alcance']);
    }

    #[Test]
    public function rechaza_un_tipo_que_no_pertenece_al_catalogo_canonico(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/clasificadores', [
            'descripcion' => 'Tipo inventado',
            'tipo_gasto' => 'TipoQueNoExiste',
            'afecta_unidad' => '1',
        ])->assertSessionHasErrors('tipo_gasto');
    }

    #[Test]
    public function intentar_reutilizar_un_codigo_existente_no_genera_duplicado(): void
    {
        // El codigo ya no se acepta desde el formulario, asi que la validacion
        // de duplicados sobra: dos clasificaciones seguidas no pueden compartir
        // codigo porque el segundo lo recibe del correlativo. Este test fija
        // esa garantia en lugar de la regla de unicidad que ya no aplica.
        $admin = $this->usuarioConRol('admin');

        $this->nuevo(['descripcion' => 'Original']);

        $this->actingAs($admin)->post('/clasificadores', [
            'codigo' => 'CG-0001',
            'descripcion' => 'Intento de duplicado',
            'tipo_gasto' => TipoGasto::Varios->value,
            'afecta_unidad' => '1',
        ])->assertSessionHasNoErrors();

        $codigo = DB::table('global.clasificador_gastos')
            ->where('descripcion', 'Intento de duplicado')
            ->value('codigo');

        $this->assertNotSame('CG-0001', $codigo);
        $this->assertMatchesRegularExpression('/^CG-\d{4}$/', (string) $codigo);
    }

    #[Test]
    public function actualizar_un_clasificador_cambia_su_alcance(): void
    {
        $admin = $this->usuarioConRol('admin');
        $filaId = $this->nuevo(['descripcion' => 'Original', 'afecta_unidad' => true, 'afecta_general' => false]);
        $codigo = DB::table('global.clasificador_gastos')->where('id_clasificador', $filaId)->value('codigo');

        $this->actingAs($admin)->put('/clasificadores/' . $filaId, [
            'codigo' => $codigo,
            'descripcion' => 'Renombrado',
            'tipo_gasto' => TipoGasto::Varios->value,
            'afecta_unidad' => '1',
            'afecta_general' => '1',
        ])->assertRedirect('/clasificadores');

        $this->assertDatabaseHas('global.clasificador_gastos', [
            'id_clasificador' => $filaId,
            'descripcion' => 'Renombrado',
            'afecta_unidad' => true,
            'afecta_general' => true,
        ]);
    }

    // =================================================================
    // BAJA
    // =================================================================

    #[Test]
    public function elimina_fisicamente_solo_si_no_tiene_gastos(): void
    {
        $admin = $this->usuarioConRol('admin');
        $huerfano = $this->nuevo(['descripcion' => 'Sin usar']);

        $this->actingAs($admin)->deleteJson('/clasificadores/' . $huerfano)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('global.clasificador_gastos', ['id_clasificador' => $huerfano]);
    }

    #[Test]
    public function desactiva_en_lugar_de_eliminar_si_tiene_gastos(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = $this->vehiculo();
        $banco = $this->banco();
        $clasificador = $this->nuevo(['descripcion' => 'En uso', 'tipo_gasto' => TipoGasto::Combustible->value]);

        $this->actingAs($admin)->post('/gastos', [
            'id_vehiculo' => $vehiculo,
            'id_clasificador' => $clasificador,
            'monto' => 500,
            'fecha_gasto' => now()->format('Y-m-d'),
            'concepto' => 'Carga inicial',
            'condicion_pago' => 'CONTADO',
            'id_banco' => $banco,
        ])->assertRedirect();

        $respuesta = $this->actingAs($admin)->deleteJson('/clasificadores/' . $clasificador);

        $respuesta->assertOk()->assertJsonPath('success', true);

        // Un clasificador con gastos debe conservarse por el historial contable.
        $estado = DB::table('global.clasificador_gastos')
            ->where('id_clasificador', $clasificador)
            ->value('estado');

        $this->assertSame(
            'INACTIVO',
            $estado,
            'Un clasificador con gastos debe desactivarse, no eliminarse.'
        );

        // Y ya no debe ofrecerse en los formularios.
        $ids = app(\App\Services\ClasificadorGastoService::class)->listar('unidad')->pluck('id_clasificador');
        $this->assertFalse($ids->contains($clasificador));
    }

    // =================================================================
    // CONTROL DE ACCESO
    // =================================================================

    #[Test]
    public function solo_el_administrador_gestiona_el_clasificador(): void
    {
        $fila = $this->nuevo(['descripcion' => 'Protegido']);

        $datos = [
            'descripcion' => 'Intruso',
            'tipo_gasto' => TipoGasto::Varios->value,
            'afecta_unidad' => '1',
        ];

        foreach (['supervisor', 'operador', 'lectura'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->post('/clasificadores', $datos)
                ->assertForbidden();

            $this->actingAs($this->usuarioConRol($rol))
                ->put('/clasificadores/' . $fila, $datos)
                ->assertForbidden();

            $this->actingAs($this->usuarioConRol($rol))
                ->delete('/clasificadores/' . $fila)
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('global.clasificador_gastos', ['descripcion' => 'Intruso']);
    }

    #[Test]
    public function cualquier_autenticado_puede_consultar_el_catalogo(): void
    {
        $this->nuevo(['descripcion' => 'Visible']);

        foreach (['admin', 'supervisor', 'operador', 'lectura'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get('/clasificadores')
                ->assertOk()
                ->assertSee('Visible');
        }
    }

    #[Test]
    public function un_visitante_sin_sesion_es_redirigido_al_login(): void
    {
        $this->get('/clasificadores')->assertRedirect(route('login'));
    }

    // =================================================================
    // SINCRONIA CON LA BASE DE DATOS
    // =================================================================

    #[Test]
    public function el_check_de_la_base_acepta_todo_el_catalogo_del_enum(): void
    {
        // El enum y el CHECK de PostgreSQL deben estar en sincronia. Si
        // divergen aparece el bug original: PHP valida un valor que la
        // base rechaza.
        $constraint = DB::selectOne(
            "SELECT pg_get_constraintdef(oid) AS def
             FROM pg_constraint
             WHERE conrelid = 'global.gastos'::regclass AND conname = 'gastos_tipo_gasto_check'"
        );

        $this->assertNotNull($constraint, 'El CHECK gastos_tipo_gasto_check deberia existir.');

        foreach (TipoGasto::values() as $valor) {
            $this->assertStringContainsString(
                "'{$valor}'",
                $constraint->def,
                "El enum declara Â«{$valor}Â» pero el CHECK de la base no lo acepta."
            );
        }
    }

    #[Test]
    public function el_catalogo_siembra_esta_poblado(): void
    {
        $total = DB::table('global.clasificador_gastos')->count();

        $this->assertGreaterThan(0, $total, 'La migracion de siembra debe dejar clasificadores.');

        foreach (['Combustible', 'Mantenimiento', 'Peaje', 'Sueldo', 'Viatico',
                  'CajaChica', 'ServiciosBasicos', 'Impuestos', 'Alquiler',
                  'Telecomunicaciones'] as $tipo) {
            $existe = DB::table('global.clasificador_gastos')->where('tipo_gasto', $tipo)->exists();
            $this->assertTrue($existe, "Falta un clasificador para el tipo Â«{$tipo}Â».");
        }
    }

    #[Test]
    public function la_siembra_cubre_ambos_alcances_para_los_tipos_operativos(): void
    {
        foreach (['Combustible', 'Mantenimiento', 'Peaje', 'Sueldo', 'Viatico'] as $tipo) {
            $hay = DB::table('global.clasificador_gastos')
                ->where('tipo_gasto', $tipo)
                ->where('afecta_unidad', true)
                ->where('estado', 'ACTIVO')
                ->exists();

            $this->assertTrue($hay, "No hay ningun clasificador de unidad para Â«{$tipo}Â»: el formulario de gastos quedaria vacio.");
        }

        foreach (['CajaChica', 'ServiciosBasicos', 'Impuestos', 'Alquiler'] as $tipo) {
            $hay = DB::table('global.clasificador_gastos')
                ->where('tipo_gasto', $tipo)
                ->where('afecta_general', true)
                ->where('estado', 'ACTIVO')
                ->exists();

            $this->assertTrue($hay, "No hay ningun clasificador general para Â«{$tipo}Â»: el formulario de gastos generales quedaria vacio.");
        }
    }
}