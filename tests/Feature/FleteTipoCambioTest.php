<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ConfiguracionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * TIPO DE CAMBIO CON EL QUE SE FACTURO CADA FLETE.
 *
 * Cada ingreso guarda los Bs/USD vigentes al registrarse. Con eso el
 * usuario que revisa facturas ve con cuanto cambio se emitio el flete,
 * sin depender de la configuracion de HOY.
 */
class FleteTipoCambioTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, mixed> */
    private function crearFlete(array $extra = []): array
    {
        $respuesta = $this->actingAs($this->usuarioConRol('admin'))
            ->postJson('/facturacion', $extra + [
                'id_vehiculo' => $this->vehiculo(),
                'monto' => 1710,
                'fecha_ingreso' => now()->format('Y-m-d'),
                'concepto' => 'TRANSPORTE DE SOYA',
            ]);

        $respuesta->assertOk()->assertJsonPath('success', true);

        $id = $respuesta->json('id_ingreso');

        return [$id, $respuesta];
    }

    private function datosUltimoFlete(int $id): ?object
    {
        return DB::table('global.ingresos')->where('id_ingreso', $id)->first();
    }

    #[Test]
    public function si_el_formulario_no_manda_cambio_se_usa_el_automatico_por_fecha(): void
    {
        [, $respuesta] = $this->crearFlete();

        // El TC ya no se digita ni se toma de la config de HOY: la regla es
        // automatica por la fecha del flete (6,96 hasta el 30/09/2025 y el
        // registro oficial BCB de esa fecha despues).
        $automatico = (new ConfiguracionService())
            ->tipoCambioParaFecha(now()->format('Y-m-d'));
        $registro = $this->datosUltimoFlete($respuesta->json('id_ingreso'));

        $this->assertSame($automatico, (float) $registro->tipo_cambio);
    }

    #[Test]
    public function el_cambio_del_formulario_se_manda_tal_cual(): void
    {
        // Un flete facturado a 11.73 queda con 11.73 para siempre, aunque
        // despues se edite la configuracion.
        [$id, ] = $this->crearFlete(['tipo_cambio' => 11.73]);
        $this->crearFlete(['tipo_cambio' => '12.05']);

        $this->assertSame(11.73, (float) $this->datosUltimoFlete($id)->tipo_cambio);
    }

    #[Test]
    public function un_cambio_fuera_de_rango_se_rechaza(): void
    {
        $this->actingAs($this->usuarioConRol('admin'))
            ->postJson('/facturacion', [
                'id_vehiculo' => $this->vehiculo(),
                'monto' => 100,
                'fecha_ingreso' => now()->format('Y-m-d'),
                'tipo_cambio' => 5000,
            ])
            ->assertJsonValidationErrors(['tipo_cambio']);
    }

    #[Test]
    public function el_listado_de_fletes_incluye_el_cambio(): void
    {
        $this->crearFlete(['tipo_cambio' => 11.73]);

        $flete = $this->actingAs($this->usuarioConRol('admin'))
            ->getJson('/api/facturacion')
            ->assertOk()
            ->json('data');

        $fleteMatch = collect($flete)->first(fn ($f) => (float) ($f['tipo_cambio'] ?? 0) === 11.73);

        $this->assertNotNull($fleteMatch, 'El listado de fletes debe exponer tipo_cambio.');
    }

    #[Test]
    public function la_factura_agrupada_informa_con_el_cambio_emitido(): void
    {
        // Dos fletes del mismo dia en una factura: el rango min/max
        // coincide, la vista muestra un solo valor.
        [$flete1, ] = $this->crearFlete(['tipo_cambio' => 11.73]);
        [$flete2, ] = $this->crearFlete(['tipo_cambio' => 11.73]);

        $factura = $this->actingAs($this->usuarioConRol('admin'))
            ->postJson('/api/facturacion/batch-facturar', [
                'ids' => [$flete1, $flete2],
                'numero_factura' => 'F-2026-001',
                'fecha_factura' => now()->format('Y-m-d'),
                'cliente_nombre' => 'INDUSTRIAS OLEAGINOSAS S.A.',
            ]);

        $factura->assertOk();

        $agrupado = $this->actingAs($this->usuarioConRol('admin'))
            ->getJson('/api/facturacion/listado')
            ->assertOk()
            ->json('data');

        $f = collect($agrupado)->first(fn ($x) => $x['numero_factura'] === 'F-2026-001');

        $this->assertNotNull($f);
        $this->assertSame(11.73, (float) $f['tipo_cambio_min']);
        $this->assertSame(11.73, (float) $f['tipo_cambio_max']);
    }

    #[Test]
    public function una_factura_con_dias_de_cambio_distinto_informa_todos_los_valores(): void
    {
        [$flete1, ] = $this->crearFlete(['tipo_cambio' => 11.5]);
        [$flete2, ] = $this->crearFlete(['tipo_cambio' => 12.0]);

        $this->actingAs($this->usuarioConRol('admin'))
            ->postJson('/api/facturacion/batch-facturar', [
                'ids' => [$flete1, $flete2],
                'numero_factura' => 'F-2026-002',
                'fecha_factura' => now()->format('Y-m-d'),
                'cliente_nombre' => 'INDUSTRIAS OLEAGINOSAS S.A.',
            ])->assertOk();

        $f = collect($this->actingAs($this->usuarioConRol('admin'))
            ->getJson('/api/facturacion/listado')
            ->json('data'))->first(fn ($x) => $x['numero_factura'] === 'F-2026-002');

        $this->assertSame(11.5, (float) $f['tipo_cambio_min']);
        $this->assertSame(12.0, (float) $f['tipo_cambio_max']);
    }

    #[Test]
    public function la_plantilla_de_alta_del_flete_muestra_el_cambio(): void
    {
        // Formulario completo /facturacion/nuevo.
        $this->actingAs($this->usuarioConRol('admin'))->get('/facturacion/nuevo')
            ->assertOk()
            ->assertSee('name="tipo_cambio"', false)
            ->assertSee('TIPO DE CAMBIO');

        // Modal del listado /facturacion.
        $html = $this->actingAs($this->usuarioConRol('admin'))->get('/facturacion')->assertOk()->getContent();
        $this->assertStringContainsString('id="nf_tipo_cambio"', $html);
        $this->assertStringContainsString('id="fletesBody"', $html);

        // Modal del dashboard.
        $htmlDash = $this->actingAs($this->usuarioConRol('admin'))->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('id="fd_tipo_cambio"', $htmlDash);
    }

    #[Test]
    public function el_schema_de_ingresos_tiene_la_columna_de_cambio(): void
    {
        $columna = DB::selectOne("
            SELECT data_type FROM information_schema.columns
            WHERE table_schema = 'global'
              AND table_name = 'ingresos'
              AND column_name = 'tipo_cambio'
        ");

        $this->assertNotNull($columna, 'tipo_cambio debe existir en global.ingresos.');
        $this->assertSame('numeric', $columna->data_type);
    }
}
