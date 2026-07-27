<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function stats()
    {
        $vehiculos = DB::table('global.vehiculos')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) as inactivos,
                SUM(CASE WHEN estado = 2 THEN 1 ELSE 0 END) as en_taller
            ")
            ->first();

        $mesActual = date('Y-m');
        $ingresosMes = DB::table('global.ingresos')
            ->where('estado_factura', '!=', 'ANULADA')
            ->whereRaw("to_char(fecha_ingreso, 'YYYY-MM') = ?", [$mesActual])
            ->sum('monto');

        $gastosMes = DB::table('global.gastos')
            ->whereRaw("to_char(fecha_gasto, 'YYYY-MM') = ?", [$mesActual])
            ->sum('monto');

        $personalActivo = DB::table('global.personal')->where('estado', 1)->count();
        $pendientesFacturar = DB::table('global.ingresos')->where('estado_factura', 'PENDIENTE')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'vehiculos' => $vehiculos,
                'ingresos_mes' => (float) $ingresosMes,
                'gastos_mes' => (float) $gastosMes,
                'balance_mes' => (float) $ingresosMes - (float) $gastosMes,
                'personal_activo' => $personalActivo,
                'pendientes_facturar' => $pendientesFacturar,
            ]
        ]);
    }
}
