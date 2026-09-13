<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GastoGeneralController extends Controller
{
    public function index()
    {
        return view('gastos-generales.index');
    }

    public function create()
    {
        $bancos = DB::table('global.bancos')->where('estado', 'ACTIVO')->orderBy('nombre_banco')->get();
        $proveedores = DB::table('global.proveedores')->where('estado', 1)->orderBy('nombre_proveedor')->get();
        return view('gastos-generales.form', ['gasto' => null, 'bancos' => $bancos, 'proveedores' => $proveedores]);
    }

    public function edit($id)
    {
        $gasto = DB::table('global.gastos_generales')->where('id_gasto_general', $id)->first();
        if (!$gasto) return redirect()->route('gastos-generales.index')->with('error', 'Gasto no encontrado');
        $bancos = DB::table('global.bancos')->where('estado', 'ACTIVO')->orderBy('nombre_banco')->get();
        $proveedores = DB::table('global.proveedores')->where('estado', 1)->orderBy('nombre_proveedor')->get();
        return view('gastos-generales.form', compact('gasto', 'bancos', 'proveedores'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'categoria' => 'required|string|in:Caja Chica,Servicios Básicos,Impuestos,Telecomunicaciones,Alquiler,Seguros,Varios',
            'concepto' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha_gasto' => 'required|date',
            'condicion_pago' => 'required|string|in:CONTADO,CREDITO',
            'id_banco' => 'nullable|integer',
            'id_proveedor' => 'nullable|integer',
            'nro_comprobante' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
        ]);

        $ultimo = DB::table('global.gastos_generales')->where('nro_documento', 'like', 'GG_%')->orderBy('id_gasto_general', 'desc')->first();
        $contador = $ultimo ? intval(substr($ultimo->nro_documento, 3)) + 1 : 1;
        $data['nro_documento'] = 'GG_' . str_pad($contador, 5, '0', STR_PAD_LEFT);

        DB::table('global.gastos_generales')->insert([
            'nro_documento' => $data['nro_documento'],
            'categoria' => $data['categoria'],
            'concepto' => $data['concepto'],
            'monto' => $data['monto'],
            'fecha_gasto' => $data['fecha_gasto'],
            'condicion_pago' => $data['condicion_pago'],
            'id_banco' => $data['id_banco'] ?? null,
            'id_proveedor' => $data['id_proveedor'] ?? null,
            'nro_comprobante' => $data['nro_comprobante'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'estado' => 'ACTIVO',
        ]);

        return redirect()->route('gastos-generales.index')->with('success', 'Gasto general registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $gasto = DB::table('global.gastos_generales')->where('id_gasto_general', $id)->first();
        if (!$gasto) return redirect()->route('gastos-generales.index')->with('error', 'Gasto no encontrado');

        $data = $request->validate([
            'categoria' => 'required|string|in:Caja Chica,Servicios Básicos,Impuestos,Telecomunicaciones,Alquiler,Seguros,Varios',
            'concepto' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha_gasto' => 'required|date',
            'condicion_pago' => 'required|string|in:CONTADO,CREDITO',
            'id_banco' => 'nullable|integer',
            'id_proveedor' => 'nullable|integer',
            'nro_comprobante' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
        ]);

        DB::table('global.gastos_generales')->where('id_gasto_general', $id)->update([
            'categoria' => $data['categoria'],
            'concepto' => $data['concepto'],
            'monto' => $data['monto'],
            'fecha_gasto' => $data['fecha_gasto'],
            'condicion_pago' => $data['condicion_pago'],
            'id_banco' => $data['id_banco'] ?? null,
            'id_proveedor' => $data['id_proveedor'] ?? null,
            'nro_comprobante' => $data['nro_comprobante'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        return redirect()->route('gastos-generales.index')->with('success', 'Gasto general actualizado exitosamente');
    }

    public function destroy($id)
    {
        DB::table('global.gastos_generales')->where('id_gasto_general', $id)->delete();
        return redirect()->route('gastos-generales.index')->with('success', 'Gasto general eliminado');
    }

    public function apiList(Request $request)
    {
        // Gastos de la tabla gastos_generales (nuevos)
        $queryNuevos = DB::table('global.gastos_generales as gg')
            ->leftJoin('global.bancos as b', 'gg.id_banco', '=', 'b.id_banco')
            ->leftJoin('global.proveedores as p', 'gg.id_proveedor', '=', 'p.id_proveedor')
            ->select(
                'gg.id_gasto_general as id',
                'gg.nro_documento',
                'gg.categoria',
                'gg.concepto',
                'gg.monto',
                'gg.fecha_gasto',
                'gg.condicion_pago',
                'gg.nro_comprobante',
                'gg.observaciones',
                'gg.estado',
                'b.nombre_banco',
                'p.nombre_proveedor',
                DB::raw("'NUEVO' as origen")
            );

        if ($request->filled('categoria')) {
            $queryNuevos->where('gg.categoria', $request->categoria);
        }
        if ($request->filled('fecha_inicio')) {
            $queryNuevos->where('gg.fecha_gasto', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $queryNuevos->where('gg.fecha_gasto', '<=', $request->fecha_fin);
        }

        $nuevos = $queryNuevos->get();

        // Gastos viejos de la tabla gastos que NO tienen vehículo (generales)
        $queryViejos = DB::table('global.gastos as g')
            ->leftJoin('global.proveedores as p', 'g.id_proveedor', '=', 'p.id_proveedor')
            ->whereNull('g.id_vehiculo')
            ->whereNotIn('g.tipo_gasto', ['Sueldo', 'Viático'])
            ->select(
                'g.id_gasto as id',
                'g.nro_documento',
                DB::raw("'Servicios Generales' as categoria"),
                'g.concepto',
                'g.monto',
                'g.fecha_gasto',
                DB::raw("'CONTADO' as condicion_pago"),
                'g.nro_documento as nro_comprobante',
                'g.descripcion as observaciones',
                DB::raw("'ACTIVO' as estado"),
                DB::raw("NULL as nombre_banco"),
                'p.nombre_proveedor',
                DB::raw("'VIEJO' as origen")
            );

        if ($request->filled('fecha_inicio')) {
            $queryViejos->where('g.fecha_gasto', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $queryViejos->where('g.fecha_gasto', '<=', $request->fecha_fin);
        }

        $viejos = $queryViejos->get();

        $data = $nuevos->concat($viejos)->sortByDesc('fecha_gasto')->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiShow($id)
    {
        $gasto = DB::table('global.gastos_generales')->where('id_gasto_general', $id)->first();
        return response()->json(['success' => true, 'data' => $gasto]);
    }
}
