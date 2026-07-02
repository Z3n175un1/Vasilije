<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GastoController extends Controller
{
    public function index()
    {
        return view('gastos.index');
    }

    public function create(Request $request)
    {
        $id_vehiculo = $request->query('id_vehiculo');
        $vehiculos = DB::table('global.vehiculos')->where('estado', '<>', 3)->orderBy('placa_vehiculo')->get();
        $proveedores = DB::table('global.proveedores')->where('estado', 1)->orderBy('nombre_proveedor')->get();
        return view('gastos.form', ['gasto' => null, 'vehiculos' => $vehiculos, 'id_vehiculo' => $id_vehiculo, 'proveedores' => $proveedores]);
    }

    public function edit($id)
    {
        $gasto = DB::table('global.gastos')->where('id_gasto', $id)->first();
        if (!$gasto) return redirect()->route('dashboard.index')->with('error', 'Gasto no encontrado');

        if ($gasto->tipo_gasto === 'Combustible') {
            $gasto->combustible = DB::table('global.combustible_detalle')->where('id_gasto', $id)->first();
        }

        $vehiculos = DB::table('global.vehiculos')->where('estado', '<>', 3)->orderBy('placa_vehiculo')->get();
        $proveedores = DB::table('global.proveedores')->where('estado', 1)->orderBy('nombre_proveedor')->get();
        return view('gastos.form', ['gasto' => $gasto, 'vehiculos' => $vehiculos, 'id_vehiculo' => null, 'proveedores' => $proveedores]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_vehiculo' => 'required|integer',
            'tipo_gasto' => 'required|string',
            'concepto' => 'required|string',
            'monto' => 'required|numeric',
            'fecha_gasto' => 'required|date',
            'descripcion' => 'nullable|string',
            'kilometraje' => 'nullable|numeric',
            'proveedor' => 'nullable|string',
            'tipo_combustible' => 'nullable|string',
            'galones' => 'nullable|numeric',
            'precio_por_galon' => 'nullable|numeric',
        ]);

        // Resolve id_proveedor from proven name
        if (!empty($data['proveedor'])) {
            $prov = DB::table('global.proveedores')->where('nombre_proveedor', $data['proveedor'])->first();
            if ($prov) $data['id_proveedor'] = $prov->id_proveedor;
        }

        $ultimo = DB::table('global.gastos')->where('nro_documento', 'like', 'E_%')->orderBy('id_gasto', 'desc')->first();
        $contador = $ultimo ? intval(substr($ultimo->nro_documento, 2)) + 1 : 1;
        $data['nro_documento'] = 'E_' . str_pad($contador, 5, '0', STR_PAD_LEFT);

        $id_gasto = DB::table('global.gastos')->insertGetId($data, 'id_gasto');
        $gasto = DB::table('global.gastos')->where('id_gasto', $id_gasto)->first();

        if ($data['tipo_gasto'] === 'Combustible' && !empty($data['galones']) && !empty($data['precio_por_galon'])) {
            DB::table('global.combustible_detalle')->insert([
                'id_gasto' => $id_gasto,
                'tipo_carburante' => $data['tipo_combustible'] ?? 'Diesel',
                'galones' => $data['galones'],
                'precio_por_galon' => $data['precio_por_galon'],
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Gasto {$gasto->nro_documento} registrado exitosamente",
                'nro_documento' => $gasto->nro_documento,
                'id_gasto' => $id_gasto,
            ]);
        }
        return redirect()->route('dashboard.index')->with('success', "Gasto {$gasto->nro_documento} registrado exitosamente");
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'id_vehiculo' => 'required|integer',
            'tipo_gasto' => 'required|string',
            'concepto' => 'required|string',
            'monto' => 'required|numeric',
            'fecha_gasto' => 'required|date',
            'descripcion' => 'nullable|string',
            'kilometraje' => 'nullable|numeric',
            'proveedor' => 'nullable|string',
            'tipo_combustible' => 'nullable|string',
            'galones' => 'nullable|numeric',
            'precio_por_galon' => 'nullable|numeric',
        ]);

        if (!empty($data['proveedor'])) {
            $prov = DB::table('global.proveedores')->where('nombre_proveedor', $data['proveedor'])->first();
            if ($prov) $data['id_proveedor'] = $prov->id_proveedor;
        }

        DB::table('global.gastos')->where('id_gasto', $id)->update($data);

        if ($data['tipo_gasto'] === 'Combustible') {
            $existing = DB::table('global.combustible_detalle')->where('id_gasto', $id)->first();
            $combData = [
                'tipo_carburante' => $data['tipo_combustible'] ?? 'Diesel',
                'galones' => $data['galones'] ?? 0,
                'precio_por_galon' => $data['precio_por_galon'] ?? 0,
            ];
            if ($existing) {
                DB::table('global.combustible_detalle')->where('id_gasto', $id)->update($combData);
            } else {
                $combData['id_gasto'] = $id;
                DB::table('global.combustible_detalle')->insert($combData);
            }
        }

        return redirect()->route('dashboard.index')->with('success', 'Gasto actualizado exitosamente');
    }

    public function destroy($id)
    {
        DB::table('global.combustible_detalle')->where('id_gasto', $id)->delete();
        DB::table('global.gastos')->where('id_gasto', $id)->delete();
        return redirect()->route('dashboard.index')->with('success', 'Gasto eliminado');
    }

    public function apiList(Request $request)
    {
        $query = DB::table('global.gastos')
            ->leftJoin('global.vehiculos', 'global.gastos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->select('global.gastos.*', 'global.vehiculos.placa_vehiculo as placa')
            ->orderBy('global.gastos.fecha_gasto', 'desc');

        if ($request->filled('id_vehiculo')) {
            $query->where('global.gastos.id_vehiculo', $request->id_vehiculo);
        }

        if ($request->filled('tipo_gasto')) {
            $query->where('global.gastos.tipo_gasto', $request->tipo_gasto);
        }

        $data = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function apiShow($id)
    {
        $gasto = DB::table('global.gastos')->where('id_gasto', $id)->first();
        if ($gasto && $gasto->tipo_gasto === 'Combustible') {
            $gasto->combustible = DB::table('global.combustible_detalle')->where('id_gasto', $id)->first();
        }
        return response()->json(['success' => true, 'data' => $gasto]);
    }
}
