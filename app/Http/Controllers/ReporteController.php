<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    public function index()
    {
        $vehiculos = DB::table('global.vehiculos')->where('estado', 1)->orderBy('placa_vehiculo')->get();
        return view('reportes.index', compact('vehiculos'));
    }

    public function filtro(Request $request)
    {
        $fechaInicio = $request->fecha_inicio ?? date('Y-m-01');
        $fechaFin = $request->fecha_fin ?? date('Y-m-d');
        $tipo = strtoupper($request->tipo ?? 'TODO');
        $idVehiculo = $request->id_vehiculo;

        $gastos = $this->getGastos($fechaInicio, $fechaFin, $tipo, $idVehiculo);
        $ingresos = $this->getIngresos($fechaInicio, $fechaFin, $tipo, $idVehiculo);
        $consumos = $this->getConsumos($fechaInicio, $fechaFin, $tipo, $idVehiculo);

        $todo = collect($gastos)->concat($ingresos)->concat($consumos)->sortByDesc('fecha')->values();

        $totalIngresos = collect($ingresos)->sum('ingreso');
        $totalEgresos = collect($gastos)->sum('egreso') + collect($consumos)->sum('egreso');

        return response()->json([
            'success' => true,
            'data' => $todo,
            'resumen' => [
                'total_ingresos' => $totalIngresos,
                'total_egresos' => $totalEgresos,
                'balance' => $totalIngresos - $totalEgresos,
                'periodo' => "$fechaInicio a $fechaFin",
                'tipo_reporte' => $tipo,
            ]
        ]);
    }

    public function financiero()
    {
        $debe = (float) DB::table('global.gastos')->sum('monto');
        $haber = (float) DB::table('global.ingresos')->sum('monto');
        $pat = (float) DB::table('global.patrimonio')->sum('valor_estimado');
        $patVehiculos = DB::table('global.vehiculos')->count() * 50000;
        $patAlmacen = (float) DB::table('global.inventario')
            ->select(DB::raw('COALESCE(SUM(stock_actual * precio_compra), 0) as total'))
            ->value('total');

        return response()->json([
            'success' => true,
            'data' => [
                'debe' => $debe,
                'haber' => $haber,
                'patrimonio' => $pat + $patVehiculos + $patAlmacen,
                'balance' => $haber - $debe,
                'detalles' => [
                    'gastos' => $debe,
                    'ingresos' => $haber,
                    'activos_fijos' => $pat + $patVehiculos,
                    'activos_almacen' => $patAlmacen,
                ]
            ]
        ]);
    }

    public function almacen()
    {
        $data = DB::table('global.inventario')
            ->select('categoria',
                DB::raw('COUNT(*) as total_items'),
                DB::raw('COALESCE(SUM(stock_actual * precio_compra), 0) as valor_total'))
            ->groupBy('categoria')
            ->orderByDesc('valor_total')
            ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function pdf(Request $request)
    {
        $fechaInicio = $request->fecha_inicio ?? date('Y-m-01');
        $fechaFin = $request->fecha_fin ?? date('Y-m-d');
        $tipo = strtoupper($request->tipo ?? 'TODO');
        $idVehiculo = $request->id_vehiculo;

        $gastos = $this->getGastos($fechaInicio, $fechaFin, $tipo, $idVehiculo);
        $ingresos = $this->getIngresos($fechaInicio, $fechaFin, $tipo, $idVehiculo);
        $consumos = $this->getConsumos($fechaInicio, $fechaFin, $tipo, $idVehiculo);

        $todo = collect($gastos)->concat($ingresos)->concat($consumos)->sortByDesc('fecha')->values();

        $totalIngresos = collect($ingresos)->sum('ingreso');
        $totalEgresos = collect($gastos)->sum('egreso') + collect($consumos)->sum('egreso');

        $vehiculo = null;
        if ($idVehiculo) {
            $vehiculo = DB::table('global.vehiculos')->where('id_vehiculo', $idVehiculo)->first();
        }

        $pdf = Pdf::loadView('reportes.pdf', compact(
            'todo', 'totalIngresos', 'totalEgresos',
            'fechaInicio', 'fechaFin', 'tipo', 'vehiculo'
        ));

        $pdf->setPaper('letter', 'landscape');

        $filename = 'reporte_' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    private function getGastos($fechaInicio, $fechaFin, $tipo, $idVehiculo = null)
    {
        $query = DB::table('global.gastos as g')
            ->leftJoin('global.vehiculos as v', 'g.id_vehiculo', '=', 'v.id_vehiculo')
            ->whereBetween('g.fecha_gasto', [$fechaInicio, $fechaFin])
            ->select(
                DB::raw("'GASTO' as tipo_registro"),
                'g.id_gasto as id',
                'g.fecha_gasto as fecha',
                'g.concepto',
                DB::raw('g.monto as egreso'),
                DB::raw('0 as ingreso'),
                'g.observaciones',
                'v.placa_vehiculo',
                'g.id_vehiculo',
                'g.tipo_gasto',
                'g.cantidad',
                'g.kilometraje',
                'g.nro_documento',
                DB::raw("COALESCE((SELECT nombre_proveedor FROM global.proveedores prov WHERE prov.id_proveedor = g.id_proveedor), g.proveedor) as proveedor")
            );

        if ($idVehiculo) $query->where('g.id_vehiculo', $idVehiculo);

        if ($tipo === 'USUARIOS') {
            $query->whereIn('g.tipo_gasto', ['Sueldos', 'Viaticos']);
        } elseif ($tipo === 'UNIDADES') {
            $query->whereIn('g.tipo_gasto', ['Combustible', 'Mantenimiento', 'Peaje']);
        }

        return $query->get()->toArray();
    }

    private function getIngresos($fechaInicio, $fechaFin, $tipo, $idVehiculo = null)
    {
        if ($tipo === 'GASTOS') return [];

        $query = DB::table('global.ingresos as i')
            ->leftJoin('global.vehiculos as v', 'i.id_vehiculo', '=', 'v.id_vehiculo')
            ->whereBetween('i.fecha_ingreso', [$fechaInicio, $fechaFin])
            ->select(
                DB::raw("'INGRESO' as tipo_registro"),
                'i.id_ingreso as id',
                'i.fecha_ingreso as fecha',
                'i.concepto',
                DB::raw('0 as egreso'),
                DB::raw('i.monto as ingreso'),
                'i.observaciones',
                'v.placa_vehiculo',
                'i.id_vehiculo',
                'i.nro_documento',
                DB::raw("'Flete/Ingreso' as tipo_gasto"),
                DB::raw('1 as cantidad'),
                DB::raw('0 as kilometraje'),
                DB::raw("'' as proveedor"),
                'i.toneladas',
                'i.kilometraje_conducido',
                DB::raw("COALESCE((SELECT CONCAT(nombres, ' ', apellidos) FROM global.personal pers WHERE pers.id_personal = i.id_personal), i.conductor_asignado) as conductor_asignado"),
                DB::raw("COALESCE(i.origen, '') as origen"),
                DB::raw("COALESCE(i.destino, '') as destino"),
                DB::raw("COALESCE(i.cliente_nombre, '') as cliente_nombre"),
                DB::raw("COALESCE(i.tipo_pago, '') as tipo_pago")
            );

        if ($idVehiculo) $query->where('i.id_vehiculo', $idVehiculo);

        return $query->get()->toArray();
    }

    private function getConsumos($fechaInicio, $fechaFin, $tipo, $idVehiculo = null)
    {
        if ($tipo !== 'TODO' && $tipo !== 'UNIDADES') return [];

        $query = DB::table('global.movimientos_inventario as m')
            ->join('global.inventario as i', 'm.id_inventario', '=', 'i.id_inventario')
            ->leftJoin('global.vehiculos as v', 'm.id_vehiculo', '=', 'v.id_vehiculo')
            ->where('m.tipo_movimiento', 'CONSUMO')
            ->whereBetween('m.fecha_movimiento', [$fechaInicio, $fechaFin])
            ->select(
                DB::raw("'GASTO' as tipo_registro"),
                'm.id_movimiento as id',
                'm.fecha_movimiento as fecha',
                DB::raw("CONCAT('CONSUMO: ', i.nombre_producto) as concepto"),
                DB::raw('(m.cantidad * m.costo_unitario) as egreso'),
                DB::raw('0 as ingreso'),
                'm.observaciones',
                'v.placa_vehiculo',
                'm.id_vehiculo',
                DB::raw("'Almacen' as tipo_gasto"),
                DB::raw('FLOOR(m.cantidad) as cantidad'),
                DB::raw('0 as kilometraje'),
                DB::raw("'' as proveedor")
            );

        if ($idVehiculo) $query->where('m.id_vehiculo', $idVehiculo);

        return $query->get()->toArray();
    }
}
