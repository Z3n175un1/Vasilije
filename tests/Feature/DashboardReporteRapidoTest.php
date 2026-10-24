<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * REPORTE RAPIDO DE UNIDAD (modal del dashboard).
 *
 * Requisitos del usuario (2026-10-21):
 *  1. La columna de monto se cambia por toneladas transportadas.
 *  2. Filtros de tipo (ingresos / gastos) y de fechas.
 *  3. El registro mas reciente aparece primero.
 *  4. Boton de borrado por fila para corregir digitacion erronea
 *     (ingresos: anula; gastos: elimina; consumos de almacen: no).
 */
class DashboardReporteRapidoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_modal_del_reporte_muestra_toneladas_filtros_y_borrado(): void
    {
        $this->seedDashboard();

        $html = $this->actingAs($this->usuarioConRol('admin'))->get('/dashboard')->getContent();

        // 1. La columna del detalle ya no es monto, ahora es toneladas.
        $this->assertStringContainsString('TONELADAS', $html);
        $this->assertStringNotContainsString('>MONTO</th>', $html);

        // 2. Barra de filtros de tipo y de fechas.
        $this->assertStringContainsString('id="rfDashTipo"', $html);
        $this->assertStringContainsString('id="rfDashDesde"', $html);
        $this->assertStringContainsString('id="rfDashHasta"', $html);
        $this->assertStringContainsString('aplicarFiltrosDash()', $html);

        // 4. Boton de borrado por fila.
        $this->assertStringContainsString('borrarMovimientoDash', $html);
        // El borrado de flete es anulacion y el de gasto es eliminacion.
        $this->assertStringContainsString('ANULAR este flete', $html);
        $this->assertStringContainsString('ELIMINAR este gasto', $html);

        // 5. El campo BS X TON es derivado de la ruta: no se puede digitar.
        $this->assertMatchesRegularExpression('/<input[^>]*id="fd_precio_ton"[^>]*readonly/', $html);
    }

    #[Test]
    public function el_reporte_muestra_primero_el_registro_mas_reciente(): void
    {
        $vehiculo = $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]);
        $this->seedDashboard();

        $this->actingAs($this->usuarioConRol('admin'))->post('/facturacion', [
            'id_vehiculo' => $vehiculo,
            'monto' => 1000.00,
            'fecha_ingreso' => '2026-10-01',
            'toneladas' => 10.00,
            'concepto' => 'FLETE ANTIGUO',
        ]);
        $this->actingAs($this->usuarioConRol('admin'))->post('/gastos', [
            'id_vehiculo' => $vehiculo,
            'id_clasificador' => $this->clasificadorDeUnidad('Combustible'),
            'monto' => 250.00,
            'fecha_gasto' => '2026-10-20',
            'concepto' => 'GASTO MAS RECIENTE',
            'condicion_pago' => 'CONTADO',
            'id_banco' => $this->banco(),
        ]);
        $this->actingAs($this->usuarioConRol('admin'))->post('/facturacion', [
            'id_vehiculo' => $vehiculo,
            'monto' => 2000.00,
            'fecha_ingreso' => '2026-10-12',
            'toneladas' => 20.00,
            'concepto' => 'FLETE INTERMEDIO',
        ]);

        $json = $this->actingAs($this->usuarioConRol('admin'))
            ->getJson("/api/reportes/filtro?id_vehiculo={$vehiculo}&fecha_inicio=2026-10-01&fecha_fin=2026-10-31")
            ->assertOk()
            ->json();

        $conceptos = array_map(static fn ($f) => $f['concepto'], $json['data']);

        // Ultimo registro primero: 20-oct gasto, 12-oct flete, 01-oct flete.
        $this->assertSame(['GASTO MAS RECIENTE', 'FLETE INTERMEDIO', 'FLETE ANTIGUO'], $conceptos);
    }

    #[Test]
    public function el_filtro_de_tipo_incluye_u_excluye_ingresos_y_gastos(): void
    {
        $vehiculo = $this->vehiculo(['id_personal' => $this->personal(), 'estado' => 1]);
        $this->seedDashboard();

        $this->actingAs($this->usuarioConRol('admin'))->post('/facturacion', [
            'id_vehiculo' => $vehiculo,
            'monto' => 5000.00,
            'fecha_ingreso' => now()->format('Y-m-d'),
            'toneladas' => 30.00,
            'concepto' => 'FLETE EQUIPO 40',
        ]);
        $this->actingAs($this->usuarioConRol('admin'))->post('/gastos', [
            'id_vehiculo' => $vehiculo,
            'id_clasificador' => $this->clasificadorDeUnidad('Combustible'),
            'monto' => 300.00,
            'fecha_gasto' => now()->format('Y-m-d'),
            'concepto' => 'CARGA COMBUSTIBLE',
            'condicion_pago' => 'CONTADO',
            'id_banco' => $this->banco(),
        ]);

        $rango = 'fecha_inicio=' . now()->format('Y-m-01') . '&fecha_fin=' . now()->format('Y-m-d');

        $todo = $this->actingAs($this->usuarioConRol('admin'))
            ->getJson("/api/reportes/filtro?id_vehiculo={$vehiculo}&{$rango}")->json('data');
        $this->assertCount(2, $todo);

        $ingresos = $this->actingAs($this->usuarioConRol('admin'))
            ->getJson("/api/reportes/filtro?id_vehiculo={$vehiculo}&tipo=INGRESOS&{$rango}")->json('data');
        $this->assertCount(1, $ingresos);
        $this->assertSame('INGRESO', $ingresos[0]['tipo_registro']);

        $gastos = $this->actingAs($this->usuarioConRol('admin'))
            ->getJson("/api/reportes/filtro?id_vehiculo={$vehiculo}&tipo=GASTOS&{$rango}")->json('data');
        $this->assertCount(1, $gastos);
        $this->assertSame('GASTO', $gastos[0]['tipo_registro']);
    }

    #[Test]
    public function el_boton_borrado_del_reporte_manda_a_la_ruta_correcta_por_tipo(): void
    {
        $this->seedDashboard();

        $html = $this->actingAs($this->usuarioConRol('admin'))->get('/dashboard')->getContent();

        // El borrado de fletes apunta a la anulacion de facturacion y el de
        // gastos a la ruta de eliminacion de gastos.
        $this->assertStringContainsString('facturacion/${item.id}', $html);
        $this->assertStringContainsString('gastos/${item.id}', $html);
    }

    private function seedDashboard(): void
    {
        \Illuminate\Support\Facades\DB::table('global.tramos')->insert([
            'origen' => 'SANTA CRUZ', 'destino' => 'LA PAZ',
            'kilometros' => 465, 'precio_total' => 4200,
            'precio_dolar_tonelada' => 185, 'created_at' => now(),
        ]);
    }
}
