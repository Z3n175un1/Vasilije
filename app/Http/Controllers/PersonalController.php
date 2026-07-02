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

        DB::table('global.personal')->where('id_personal', $id)->update($data);
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
}
