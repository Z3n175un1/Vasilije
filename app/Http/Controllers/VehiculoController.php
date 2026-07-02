<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehiculoController extends Controller
{
    public function index()
    {
        return view('vehiculos.index');
    }

    public function create()
    {
        return view('vehiculos.form', ['vehiculo' => null]);
    }

    public function edit($id)
    {
        $vehiculo = DB::table('global.vehiculos')->where('id_vehiculo', $id)->first();
        if (!$vehiculo) {
            return redirect()->route('vehiculos.index')->with('error', 'Vehículo no encontrado');
        }
        return view('vehiculos.form', ['vehiculo' => $vehiculo]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'placa_vehiculo' => 'required|string|max:20',
            'tipo_vehiculo' => 'required|string',
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'anho' => 'nullable|integer',
            'color' => 'nullable|string',
            'capacidad' => 'nullable|numeric',
            'kilometraje' => 'nullable|numeric',
            'estado' => 'required|integer',
        ]);

        DB::table('global.vehiculos')->insert($data);
        return redirect()->route('vehiculos.index')->with('success', 'Vehículo registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'placa_vehiculo' => 'required|string|max:20',
            'tipo_vehiculo' => 'required|string',
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'anho' => 'nullable|integer',
            'color' => 'nullable|string',
            'capacidad' => 'nullable|numeric',
            'kilometraje' => 'nullable|numeric',
            'estado' => 'required|integer',
        ]);

        DB::table('global.vehiculos')->where('id_vehiculo', $id)->update($data);
        return redirect()->route('vehiculos.index')->with('success', 'Vehículo actualizado exitosamente');
    }

    public function destroy($id)
    {
        DB::table('global.vehiculos')->where('id_vehiculo', $id)->update(['estado' => 3]);
        return redirect()->route('vehiculos.index')->with('success', 'Vehículo dado de baja');
    }

    public function apiShow($id)
    {
        $vehiculo = DB::table('global.vehiculos')
            ->leftJoin('global.personal', 'global.vehiculos.id_personal', '=', 'global.personal.id_personal')
            ->select(
                'global.vehiculos.*',
                DB::raw("CONCAT(global.personal.nombres, ' ', global.personal.apellidos) as conductor")
            )
            ->where('global.vehiculos.id_vehiculo', $id)
            ->first();

        return response()->json(['success' => true, 'data' => $vehiculo]);
    }

    public function vender(Request $request)
    {
        $request->validate(['id_vehiculo' => 'required|integer']);
        $vehiculo = DB::table('global.vehiculos')->where('id_vehiculo', $request->id_vehiculo)->first();
        if (!$vehiculo) {
            return response()->json(['success' => false, 'message' => 'Vehículo no encontrado']);
        }
        if ($vehiculo->estado == 3) {
            return response()->json(['success' => false, 'message' => 'El vehículo ya está marcado como VENDIDO']);
        }
        DB::table('global.vehiculos')->where('id_vehiculo', $request->id_vehiculo)->update(['estado' => 3]);
        return response()->json(['success' => true, 'message' => 'Vehículo marcado como VENDIDO']);
    }

    public function apiList(Request $request)
    {
        $query = DB::table('global.vehiculos')
            ->leftJoin('global.personal', 'global.vehiculos.id_personal', '=', 'global.personal.id_personal')
            ->select(
                'global.vehiculos.*',
                DB::raw("CONCAT(global.personal.nombres, ' ', global.personal.apellidos) as conductor"),
                DB::raw('(SELECT COALESCE(SUM(monto), 0) FROM global.ingresos WHERE id_vehiculo = global.vehiculos.id_vehiculo AND estado_factura != \'ANULADA\') as total_ingresos'),
                DB::raw('(SELECT COALESCE(SUM(monto), 0) FROM global.gastos WHERE id_vehiculo = global.vehiculos.id_vehiculo) as total_gastos')
            );

        if ($request->filled('estado')) {
            $query->where('global.vehiculos.estado', $request->estado);
        }

        if ($request->filled('busqueda')) {
            $query->where(function($q) use ($request) {
                $q->where('global.vehiculos.placa_vehiculo', 'like', '%' . $request->busqueda . '%')
                  ->orWhere('global.vehiculos.marca', 'like', '%' . $request->busqueda . '%');
            });
        }

        $data = $query->orderBy('global.vehiculos.id_vehiculo', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => ['total' => count($data)]
        ]);
    }
}
