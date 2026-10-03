<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoFactura;
use App\Enums\EstadoVehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * PANEL DE CONTROL: el centro operativo de la aplicacion.
 *
 * La agregacion se hace en SQL (no descargando filas y sumando en PHP)
 * y con funciones de fecha parametrizadas, no interpoladas.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard.index', [
            'estados' => EstadoVehiculo::cases(),
        ]);
    }

    public function stats(): JsonResponse
    {
        $vehiculos = DB::table('global.vehiculos')
            ->selectRaw("
                COUNT(*) as total,
                COUNT(*) FILTER (WHERE estado = 1) as activos,
                COUNT(*) FILTER (WHERE estado = 0) as inactivos,
                COUNT(*) FILTER (WHERE estado = 2) as en_taller,
                COUNT(*) FILTER (WHERE estado = 3) as vendidos
            ")
            ->first();

        $mes = now()->format('Y-m');

        $ingresosMes = DB::table('global.ingresos')
            ->where('estado_factura', '!=', EstadoFactura::Anulada->value)
            ->whereRaw("to_char(fecha_ingreso, 'YYYY-MM') = ?", [$mes])
            ->sum('monto');

        $gastosMes = DB::table('global.gastos')
            ->whereRaw("to_char(fecha_gasto, 'YYYY-MM') = ?", [$mes])
            ->sum('monto');

        $devolucionesMes = DB::table('global.gastos')
            ->where('es_devolucion', true)
            ->whereRaw("to_char(fecha_gasto, 'YYYY-MM') = ?", [$mes])
            ->sum('monto');

        return response()->json([
            'success' => true,
            'data' => [
                'vehiculos' => $vehiculos,
                'mes' => $mes,
                'ingresos_mes' => round((float) $ingresosMes, 2),
                'gastos_mes' => round((float) $gastosMes, 2),
                'devoluciones_mes' => round((float) $devolucionesMes, 2),
                'balance_mes' => round((float) $ingresosMes - (float) $gastosMes, 2),
                'personal_activo' => DB::table('global.personal')->where('estado', 1)->count(),
                'pendientes_facturar' => DB::table('global.ingresos')
                    ->where('estado_factura', EstadoFactura::Pendiente->value)
                    ->count(),
                'stock_bajo' => DB::table('global.inventario')
                    ->where('estado', 'ACTIVO')
                    ->whereColumn('stock_actual', '<=', 'stock_minimo')
                    ->count(),
            ],
        ]);
    }
}