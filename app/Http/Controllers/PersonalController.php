<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonalController extends Controller
{
    public function index()
    {
        return view('personal.index');
    }

    public function create()
    {
        return view('personal.form', ['personal' => null]);
    }

    public function edit($id)
    {
        $personal = DB::table('global.personal')->where('id_personal', $id)->first();
        if (!$personal) return redirect()->route('personal.index')->with('error', 'Personal no encontrado');
        return view('personal.form', ['personal' => $personal]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'ci' => 'nullable|string|max:20',
            'cargo' => 'required|string|max:50',
            'telefono' => 'nullable|string|max:20',
            'licencia' => 'nullable|string|max:20',
            'sueldo' => 'nullable|numeric',
            'direccion' => 'nullable|string',
            'email' => 'nullable|email|max:100',
            'estado' => 'required|integer',
        ]);

        $allowed = ['nombres', 'apellidos', 'ci', 'cargo', 'telefono', 'licencia', 'sueldo', 'direccion', 'email', 'estado'];
        $data = array_filter($data, function($key) use ($allowed) {
            return in_array($key, $allowed);
        }, ARRAY_FILTER_USE_KEY);

        DB::table('global.personal')->insert($data);
        return redirect()->route('personal.index')->with('success', 'Personal registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'ci' => 'nullable|string|max:20',
            'cargo' => 'required|string|max:50',
            'telefono' => 'nullable|string|max:20',
            'licencia' => 'nullable|string|max:20',
            'sueldo' => 'nullable|numeric',
            'direccion' => 'nullable|string',
            'email' => 'nullable|email|max:100',
            'estado' => 'required|integer',
        ]);

        $allowed = ['nombres', 'apellidos', 'ci', 'cargo', 'telefono', 'licencia', 'sueldo', 'direccion', 'email', 'estado'];
        $filtered = array_filter($data, function($key) use ($allowed) {
            return in_array($key, $allowed);
        }, ARRAY_FILTER_USE_KEY);

        DB::table('global.personal')->where('id_personal', $id)->update($filtered);
        return redirect()->route('personal.index')->with('success', 'Personal actualizado exitosamente');
    }

    public function destroy($id)
    {
        $tieneVehiculo = DB::table('global.vehiculos')->where('id_personal', $id)->exists();
        if ($tieneVehiculo) {
            DB::table('global.personal')->where('id_personal', $id)->update(['estado' => 0]);
            return redirect()->route('personal.index')->with('success', 'Personal desactivado (tiene vehículos asignados)');
        }
        DB::table('global.personal')->where('id_personal', $id)->delete();
        return redirect()->route('personal.index')->with('success', 'Personal eliminado');
    }

    public function apiList()
    {
        $data = DB::table('global.personal')->orderBy('nombres')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiShow($id)
    {
        $personal = DB::table('global.personal')->where('id_personal', $id)->first();
        return response()->json(['success' => true, 'data' => $personal]);
    }

    public function sueldo($id)
    {
        $personal = DB::table('global.personal')->where('id_personal', $id)->first();
        if (!$personal) return redirect()->route('personal.index')->with('error', 'Personal no encontrado');
        return view('personal.sueldo', compact('personal'));
    }

    public function viatico($id)
    {
        $personal = DB::table('global.personal')->where('id_personal', $id)->first();
        if (!$personal) return redirect()->route('personal.index')->with('error', 'Personal no encontrado');
        return view('personal.viatico', compact('personal'));
    }

    public function storeGasto(Request $request)
    {
        $data = $request->validate([
            'id_personal' => 'required|integer',
            'tipo_gasto' => 'required|string|in:Sueldo,Viático',
            'concepto' => 'required|string',
            'monto' => 'required|numeric',
            'fecha_gasto' => 'required|date',
            'descripcion' => 'nullable|string',
        ]);

        $ultimo = DB::table('global.gastos')->where('nro_documento', 'like', 'E_%')->orderBy('id_gasto', 'desc')->first();
        $contador = $ultimo ? intval(substr($ultimo->nro_documento, 2)) + 1 : 1;
        $data['nro_documento'] = 'E_' . str_pad($contador, 5, '0', STR_PAD_LEFT);
        $data['fecha_gasto'] = $data['fecha_gasto'] ?? date('Y-m-d');

        DB::table('global.gastos')->insert([
            'id_vehiculo' => null,
            'id_personal' => $data['id_personal'],
            'tipo_gasto' => $data['tipo_gasto'],
            'concepto' => $data['concepto'],
            'monto' => $data['monto'],
            'fecha_gasto' => $data['fecha_gasto'],
            'descripcion' => $data['descripcion'] ?? null,
            'nro_documento' => $data['nro_documento'],
        ]);

        return redirect()->route('personal.index')->with('success', ucfirst($data['tipo_gasto']) . ' registrado exitosamente');
    }
}
