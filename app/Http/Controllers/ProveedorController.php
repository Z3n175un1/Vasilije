<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProveedorController extends Controller
{
    public function index()
    {
        return view('proveedores.index');
    }

    public function create()
    {
        return view('proveedores.form', ['proveedor' => null]);
    }

    public function edit($id)
    {
        $proveedor = DB::table('global.proveedores')->where('id_proveedor', $id)->first();
        if (!$proveedor) return redirect()->route('proveedores.index')->with('error', 'Proveedor no encontrado');
        return view('proveedores.form', ['proveedor' => $proveedor]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nit_ci' => 'nullable|string|max:50',
            'nombre_proveedor' => 'required|string|max:200',
            'contacto' => 'nullable|string|max:200',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'direccion' => 'nullable|string',
            'tipo_proveedor' => 'nullable|string|max:50',
        ]);
        $data['tipo_proveedor'] = $data['tipo_proveedor'] ?: 'GENERAL';
        $data['estado'] = 1;

        DB::table('global.proveedores')->insert($data);
        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nit_ci' => 'nullable|string|max:50',
            'nombre_proveedor' => 'required|string|max:200',
            'contacto' => 'nullable|string|max:200',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'direccion' => 'nullable|string',
            'tipo_proveedor' => 'nullable|string|max:50',
        ]);
        $data['tipo_proveedor'] = $data['tipo_proveedor'] ?: 'GENERAL';

        DB::table('global.proveedores')->where('id_proveedor', $id)->update($data);
        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado exitosamente');
    }

    public function destroy($id)
    {
        DB::table('global.proveedores')->where('id_proveedor', $id)->delete();
        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado');
    }

    public function apiList(Request $request)
    {
        $query = DB::table('global.proveedores');
        if ($request->filled('busqueda')) {
            $q = $request->busqueda;
            $query->where(function ($w) use ($q) {
                $w->where('nombre_proveedor', 'ilike', "%{$q}%")
                  ->orWhere('nit_ci', 'ilike', "%{$q}%")
                  ->orWhere('contacto', 'ilike', "%{$q}%");
            });
        }

        $orden = $request->orden ?? 'abc';
        switch ($orden) {
            case 'z_a':
                $query->orderBy('nombre_proveedor', 'desc');
                break;
            case 'ultimo':
                $query->orderBy('id_proveedor', 'desc');
                break;
            default:
                $query->orderBy('nombre_proveedor');
        }

        $data = $query->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiShow($id)
    {
        $proveedor = DB::table('global.proveedores')->where('id_proveedor', $id)->first();
        return response()->json(['success' => true, 'data' => $proveedor]);
    }
}
