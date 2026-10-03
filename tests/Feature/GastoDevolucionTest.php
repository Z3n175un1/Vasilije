<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TipoGasto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * REGLA DE DEVOLUCION
 * -------------------
 * Un monto NEGATIVO en el registro de gastos significa DEVOLUCION.
 *
 * Antes de la intervencion esto era incoherente de punta a punta:
 *  - El formulario de unidad ofrecia el boton de confirmacion, pero el
 *    backend no distinguia nada: la devolucion quedaba registrada como
 *    "Pagado".
 *  - El formulario de generales validaba `min:0`, de modo que la devolucion
 *    era IMPOSIBLE aunque la vista permitiera escribir el monto.
 *  - El reporte lo sumaba como egreso sin marcarlo.
 *
 * Lo que se verifica:
 *  1. El servidor acepta el monto negativo y lo marca como devolucion.
 *  2. Una devolucion queda forzada a estado "Anulado": no puede haber algo
 *     "pagado" que se esta devolviendo.
 *  3. El reporte la refleja restando al total de egresos y la identifica.
 *  4. El monto cero se rechaza (ni gasto ni devolucion).
 *  5. El texto de confirmacion de la interfaz es el pactado.
 *
 * @uses \App\Services\GastoService
 * @uses \App\Http\Controllers\GastoController
 * @uses \App\Http\Controllers\GastoGeneralController
 */
class GastoDevolucionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function gastoUnidad(float $monto, array $extra = []): array
    {
        return array_merge([
            'id_vehiculo' => $this->vehiculo(),
            'id_clasificador' => $this->clasificadorDeUnidad(TipoGasto::Combustible->value),
            'monto' => $monto,
            'fecha_gasto' => now()->format('Y-m-d'),
            'concepto' => $monto < 0 ? 'Devolucion por carga sobrante' : 'Compra de combustible',
            'condicion_pago' => 'CONTADO',
            'id_banco' => $this->banco(),
        ], $extra);
    }

    // =================================================================
    // GASTO DE UNIDAD
    // =================================================================

    #[Test]
    public function acepta_un_monto_negativo_y_lo_marca_como_devolucion(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)
            ->post('/gastos', $this->gastoUnidad(-250.00))
            ->assertRedirect()
            ->assertSessionHas('success');

        $gasto = DB::table('global.gastos')->latest('id_gasto')->first();

        $this->assertNotNull($gasto, 'El gasto de devolucion debió registrarse.');
        $this->assertEquals(-250.00, (float) $gasto->monto);
        $this->assertTrue((bool) $gasto->es_devolucion, 'Un monto negativo debe marcar es_devolucion.');
    }

    #[Test]
    public function una_devolucion_queda_forzada_a_estado_anulado(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/gastos', $this->gastoUnidad(-100.00));

        $gasto = DB::table('global.gastos')->latest('id_gasto')->first();

        // No puede haber algo "pagado" que se este devolviendo.
        $this->assertSame(
            'Anulado',
            $gasto->estado_pago,
            'Una devolución debe quedar Anulada, no Pagada.'
        );
    }

    #[Test]
    public function un_monto_positivo_no_se_marca_como_devolucion(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/gastos', $this->gastoUnidad(500.00));

        $gasto = DB::table('global.gastos')->latest('id_gasto')->first();

        $this->assertFalse((bool) $gasto->es_devolucion);
        $this->assertSame('Pagado', $gasto->estado_pago);
    }

    #[Test]
    public function rechaza_un_monto_cero(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)
            ->post('/gastos', $this->gastoUnidad(0))
            ->assertSessionHasErrors('monto');

        $this->assertDatabaseCount('global.gastos', 0);
    }

    #[Test]
    public function la_devolucion_lleva_su_propio_numero_de_documento(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/gastos', $this->gastoUnidad(-50.00));
        $primera = DB::table('global.gastos')->latest('id_gasto')->value('nro_documento');

        $this->actingAs($admin)->post('/gastos', $this->gastoUnidad(75.00));
        $segunda = DB::table('global.gastos')->latest('id_gasto')->value('nro_documento');

        $this->assertNotSame($primera, $segunda, 'Cada documento debe numerarse por separado.');
        $this->assertStringStartsWith('E_', $primera);
    }

    // =================================================================
    // GASTOS GENERALES
    // =================================================================

    #[Test]
    public function un_gasto_general_tambien_admite_devolucion(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/gastos-generales', [
            'id_clasificador' => $this->clasificadorGeneral(TipoGasto::Impuestos->value),
            'concepto' => 'Devolucion de impuesto',
            'monto' => -300.00,
            'fecha_gasto' => now()->format('Y-m-d'),
            'condicion_pago' => 'CONTADO',
            'id_banco' => $this->banco(),
        ])->assertRedirect()->assertSessionHas('success');

        $gasto = DB::table('global.gastos_generales')->latest('id_gasto_general')->first();

        $this->assertNotNull($gasto);
        $this->assertEquals(-300.00, (float) $gasto->monto);
        $this->assertTrue((bool) $gasto->es_devolucion);
    }

    #[Test]
    public function un_gasto_general_cero_se_rechaza(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/gastos-generales', [
            'id_clasificador' => $this->clasificadorGeneral(TipoGasto::Impuestos->value),
            'concepto' => 'Importe en cero',
            'monto' => 0,
            'fecha_gasto' => now()->format('Y-m-d'),
            'condicion_pago' => 'CONTADO',
        ])->assertSessionHasErrors('monto');
    }

    #[Test]
    public function el_gasto_general_deriva_la_categoria_del_clasificador(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/gastos-generales', [
            'id_clasificador' => $this->clasificadorGeneral(TipoGasto::Alquiler->value),
            'concepto' => 'Alquiler del galpon',
            'monto' => 1200.00,
            'fecha_gasto' => now()->format('Y-m-d'),
            'condicion_pago' => 'CONTADO',
            'id_banco' => $this->banco(),
        ]);

        $gasto = DB::table('global.gastos_generales')->latest('id_gasto_general')->first();

        // `categoria` deja de ser texto libre y pasa a ser el codigo canonico.
        $this->assertSame(TipoGasto::Alquiler->value, $gasto->categoria);
    }

    #[Test]
    public function la_caja_chica_no_exige_cuenta_bancaria(): void
    {
        $admin = $this->usuarioConRol('admin');

        // La caja chica ES efectivo en mano: exigir una cuenta bancaria
        // seria un error de dominio, no una validacion.
        $this->actingAs($admin)->post('/gastos-generales', [
            'id_clasificador' => $this->clasificadorGeneral(TipoGasto::CajaChica->value),
            'concepto' => 'Compra de papelería',
            'monto' => 80.00,
            'fecha_gasto' => now()->format('Y-m-d'),
            'condicion_pago' => 'CONTADO',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('global.gastos_generales', 1);
    }

    #[Test]
    public function un_gasto_general_contado_sin_clasificador_de_caja_chica_si_exige_banco(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/gastos-generales', [
            'id_clasificador' => $this->clasificadorGeneral(TipoGasto::ServiciosBasicos->value),
            'concepto' => 'Factura de luz',
            'monto' => 450.00,
            'fecha_gasto' => now()->format('Y-m-d'),
            'condicion_pago' => 'CONTADO',
        ])->assertSessionHasErrors('id_banco');
    }

    // =================================================================
    // EFECTO EN REPORTES
    // =================================================================

    #[Test]
    public function el_reporte_resta_la_devolucion_al_total_de_egresos(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = $this->vehiculo();
        $banco = $this->banco();
        $clasificador = $this->clasificadorDeUnidad(TipoGasto::Combustible->value);

        $base = [
            'id_vehiculo' => $vehiculo,
            'id_clasificador' => $clasificador,
            'fecha_gasto' => now()->format('Y-m-d'),
            'condicion_pago' => 'CONTADO',
            'id_banco' => $banco,
        ];

        $this->actingAs($admin)->post('/gastos', $base + ['monto' => 400.00, 'concepto' => 'Gasto']);
        $this->actingAs($admin)->post('/gastos', $base + ['monto' => -150.00, 'concepto' => 'Devolucion']);

        $respuesta = $this->actingAs($admin)->getJson('/api/reportes/filtro?fecha_inicio=' . now()->format('Y-m-01') . '&fecha_fin=' . now()->format('Y-m-d'));

        $respuesta->assertOk();

        $resumen = $respuesta->json('resumen');

        $this->assertEquals(250.00, (float) $resumen['total_egresos'], '400 - 150 = 250.');
        $this->assertEquals(-150.00, (float) $resumen['total_devoluciones']);
    }

    #[Test]
    public function el_reporte_identifica_cuales_movimientos_son_devoluciones(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/gastos', $this->gastoUnidad(-90.00));

        $respuesta = $this->actingAs($admin)->getJson('/api/reportes/filtro?fecha_inicio=' . now()->format('Y-m-01') . '&fecha_fin=' . now()->format('Y-m-d'));

        $lineas = $respuesta->json('data');
        $devoluciones = array_filter($lineas, static fn ($l) => !empty($l['es_devolucion']));

        $this->assertCount(1, $devoluciones, 'La devolucion debe quedar marcada en el reporte.');
        $this->assertEquals('Devolucion por carga sobrante', reset($devoluciones)['concepto']);
    }

    // =================================================================
    // INTERFAZ
    // =================================================================

    #[Test]
    public function el_formulario_de_unidad_pide_confirmacion_de_devolucion(): void
    {
        $admin = $this->usuarioConRol('admin');

        $contenido = $this->actingAs($admin)->get('/gastos/crear')->getContent();

        // El texto pactado con el usuario, verbatim.
        $this->assertStringContainsString(
            '¿ESTÁ SEGURO DE REGISTRAR ESTA DEVOLUCIÓN?',
            $contenido,
            'El boton REGISTRAR GASTO debe pedir confirmacion al detectar un monto negativo.'
        );

        // Y debe existir el aviso previo visible en el formulario.
        $this->assertStringContainsString('DEVOLUCIÓN DETECTADA', $contenido);
    }

    #[Test]
    public function el_formulario_de_gastos_generales_pide_confirmacion_de_devolucion(): void
    {
        $admin = $this->usuarioConRol('admin');

        $contenido = $this->actingAs($admin)->get('/gastos-generales/nuevo')->getContent();

        $this->assertStringContainsString('¿ESTÁ SEGURO DE REGISTRAR ESTA DEVOLUCIÓN?', $contenido);
        $this->assertStringContainsString('DEVOLUCIÓN DETECTADA', $contenido);
    }

/**
 * Los dos formularios de devolucion deben enviar el registro con
 * `form.submit()` y no con `requestSubmit()`.
 *
 * `requestSubmit()` vuelve a disparar el evento submit, con lo que el
 * manejador se reentra, vuelve a abrir la confirmacion y el formulario
 * queda en bucle pidiendo confirmacion sin llegar a enviarse nunca.
 *
 * `form.submit()` envia de forma nativa sin re-disparar el evento. Es
 * seguro porque el manejador solo se alcanza despues de que el navegador
 * ya valido los campos requeridos.
 *
 * @return list<array{0:string}>
 */
    public static function formulariosDeGasto(): array
    {
        return [
            ['/gastos/crear'],
            ['/gastos-generales/nuevo'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('formulariosDeGasto')]
    public function la_devolucion_se_envia_sin_re_disparar_el_evento_submit(string $ruta): void
    {
        $admin = $this->usuarioConRol('admin');

        $contenido = $this->actingAs($admin)->get($ruta)->getContent();

        // Se revisa solo el codigo: los comentarios explican por que se usa
        // `submit()` y no deben contar como uso de `requestSubmit()`.
        $sinComentarios = preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $contenido) ?? '';

        $this->assertStringContainsString('form.submit()', $sinComentarios, "{$ruta}: debe enviar el formulario.");

        $this->assertDoesNotMatchRegularExpression(
            '/requestSubmit/',
            $sinComentarios,
            "{$ruta}: `requestSubmit()` re-dispara el submit y deja el formulario en bucle."
        );

        // El popup de "registrando" no puede cerrarse a mitad de camino.
        $this->assertStringContainsString('allowEscapeKey: false', $sinComentarios);
        $this->assertStringContainsString('showConfirmButton: false', $sinComentarios);
    }

    #[Test]
    public function el_formulario_admite_montos_negativos(): void
    {
        $admin = $this->usuarioConRol('admin');

        $contenido = $this->actingAs($admin)->get('/gastos/crear')->getContent();

        // El input no debe llevar min="0": impediría escribir la devolución.
        $this->assertStringNotContainsString('name="monto" id="montoInput" value="" required placeholder="0.00" min="0"', $contenido);
    }

    #[Test]
    public function el_boton_registrar_gasto_sigue_being_el_que_confirma(): void
    {
        $admin = $this->usuarioConRol('admin');

        $contenido = $this->actingAs($admin)->get('/gastos/crear')->getContent();

        $this->assertStringContainsString('REGISTRAR GASTO', $contenido);
        // La confirmacion se engancha al submit del formulario, que es el que
        // dispara ese boton.
        $this->assertStringContainsString("form.addEventListener('submit'", $contenido);
    }

    // =================================================================
    // COHERENCIA DEL SERVICIO
    // =================================================================

    #[Test]
    public function el_servicio_distingue_los_tres_casos_de_monto(): void
    {
        $servicio = app(\App\Services\GastoService::class);

        $this->assertFalse($servicio::esDevolucion(100.0));
        $this->assertFalse($servicio::esDevolucion(0.0));
        $this->assertTrue($servicio::esDevolucion(-0.01));
        $this->assertTrue($servicio::esDevolucion(-1000000.0));
    }

    #[Test]
    public function una_devolucion_tambien_exige_banco_si_es_contado(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/gastos', $this->gastoUnidad(-50.00, [
            'condicion_pago' => 'CONTADO',
            'id_banco' => null,
        ]))->assertSessionHasErrors('id_banco');
    }

    #[Test]
    public function una_devolucion_a_credito_exige_proveedor(): void
    {
        $admin = $this->usuarioConRol('admin');

        $this->actingAs($admin)->post('/gastos', $this->gastoUnidad(-50.00, [
            'condicion_pago' => 'CREDITO',
            'id_proveedor' => null,
        ]))->assertSessionHasErrors('id_proveedor');
    }
}