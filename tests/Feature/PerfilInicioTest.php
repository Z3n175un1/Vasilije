<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * INICIO ("/documentos"): vista rapida personal.
 *
 * Reglas de este diseño solicitadas por el usuario:
 *  - Es un "mi perfil": ficha propia, rol y accesos, no un resumen del
 *    negocio. NADA de montos ni totales economicos (eso vive en REPORTES,
 *    al que solo llegan los roles autorizados).
 *  - La lista de acciones muestra SOLO lo que hizo el propio usuario.
 */
class PerfilInicioTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function muestra_la_ficha_del_usuario_y_su_rol(): void
    {
        $usuario = $this->usuarioConRol('supervisor');

        $respuesta = $this->actingAs($usuario)
            ->get('/documentos')
            ->assertOk();

        $this->assertStringContainsString('Ficha de perfil', $respuesta->getContent());
        $this->assertStringContainsString($usuario->nombres, $respuesta->getContent());
        $this->assertStringContainsString('Supervisor', $respuesta->getContent());
    }

    #[Test]
    public function un_usuario_de_solo_lectura_tambien_ve_su_perfil(): void
    {
        $this->actingAs($this->usuarioConRol('lectura'))
            ->get('/documentos')
            ->assertOk();
    }

    #[Test]
    public function muestra_solo_las_acciones_del_propio_usuario(): void
    {
        $usuario = $this->usuarioConRol('admin');
        $otroUsuario = $this->usuarioConRol('operador');

        $idUsuario = (int) $usuario->getKey();
        $idOtro = (int) $otroUsuario->getKey();
        $ahora = now();

        // Lo que hago YO.
        DB::table('global.logs_actividad')->insert([
            'id_usuario' => $idUsuario,
            'usuario' => $usuario->usuario,
            'accion' => 'FLETE_REGISTRADO',
            'modulo' => 'facturacion',
            'descripcion' => 'Flete F-1001',
            'fecha_evento' => $ahora,
        ]);

        // Y una accion de OTRO usuario, con la marca "SECRETO";
        // NO debe asomarse.
        DB::table('global.logs_actividad')->insert([
            'id_usuario' => $idOtro,
            'usuario' => $otroUsuario->usuario,
            'accion' => 'FLETE_REGISTRADO',
            'modulo' => 'facturacion',
            'descripcion' => 'SECRETO_ACCION_AJENA',
            'fecha_evento' => $ahora,
        ]);

        $contenido = $this->actingAs($usuario)
            ->get('/documentos')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Flete registrado', $contenido);
        $this->assertStringNotContainsString('SECRETO_ACCION_AJENA', $contenido);
    }

    #[Test]
    public function la_accion_mas_reciente_aparece_primero(): void
    {
        $usuario = $this->usuarioConRol('admin');
        $idUsuario = (int) $usuario->getKey();

        DB::table('global.logs_actividad')->insert([
            ['id_usuario' => $idUsuario, 'usuario' => $usuario->usuario, 'accion' => 'ACCION_VIEJA', 'modulo' => 'almacen', 'descripcion' => 'vencida', 'fecha_evento' => now()->subHours(2)],
            ['id_usuario' => $idUsuario, 'usuario' => $usuario->usuario, 'accion' => 'ACCION_NUEVA', 'modulo' => 'almacen', 'descripcion' => 'reciente', 'fecha_evento' => now()],
        ]);

        $contenido = $this->actingAs($usuario)->get('/documentos')->assertOk()->getContent();

        // Orden DESC: la mas nueva va ANTES de la vieja (la vista
        // humaniza la accion: "ACCION_NUEVA" => "Accion nueva").
        $this->assertTrue(
            mb_strpos($contenido, 'Accion nueva') < mb_strpos($contenido, 'Accion vieja'),
            'Las acciones no vienen en orden DESC por fecha.'
        );
    }

    #[Test]
    public function el_perfil_no_filtra_datos_del_negocio(): void
    {
        // Un flete con concepto inusual: si la vista de perfil muestra
        // datos del negocio, el concepto no puede verse aqui.
        $usuario = $this->usuarioConRol('admin');
        $this->actingAs($usuario)->postJson('/facturacion', [
            'id_vehiculo' => $this->vehiculo(),
            'monto' => 1710,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'concepto' => 'TRANSPORTE_SECRETO',
        ])->assertOk();

        $contenido = $this->actingAs($usuario)->get('/documentos')->assertOk()->getContent();

        $this->assertStringNotContainsString('TRANSPORTE_SECRETO', $contenido);
    }

    #[Test]
    public function bitacora_vacia_muestra_estado_vacio(): void
    {
        $this->actingAs($this->usuarioConRol('admin'))
            ->get('/documentos')
            ->assertOk()
            ->assertSee('Todavía no hay acciones registradas.');
    }

    // =================================================================
    // CATALOGOS FINANCIEROS (puedeVerFinanzas, solo admin)
    // =================================================================

    #[Test]
    public function el_admin_ve_las_opciones_financieras_de_catalogos(): void
    {
        $contenido = $this->actingAs($this->usuarioConRol('admin'))
            ->get('/documentos')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-tabla="bancos"', $contenido);
        $this->assertStringContainsString('data-tabla="config"', $contenido);
    }

    #[Test]
    public function los_demas_roles_no_ven_las_opciones_financieras_de_catalogos(): void
    {
        foreach (['supervisor', 'operador', 'lectura'] as $rol) {
            $contenido = $this->actingAs($this->usuarioConRol($rol))
                ->get('/documentos')
                ->assertOk()
                ->getContent();

            $this->assertStringNotContainsString('data-tabla="bancos"', $contenido, "El rol $rol no debe ver Bancos aqui.");
            $this->assertStringNotContainsString('data-tabla="config"', $contenido, "El rol $rol no debe ver Configuración aqui.");
        }
    }
}
