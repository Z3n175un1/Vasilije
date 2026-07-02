<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrupoController extends Controller
{
    public function index()
    {
        return view('grupos.index');
    }

    public function create()
    {
        return view('grupos.form', ['grupo' => null]);
    }

    public function edit($id)
    {
        $grupo = DB::table('global.categorias_almacen')->where('id_categoria', $id)->first();
        if (!$grupo) return redirect()->route('grupos.index')->with('error', 'Grupo no encontrado');
        return view('grupos.form', ['grupo' => $grupo]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100|unique:global.categorias_almacen,nombre',
            'descripcion' => 'nullable|string',
        ]);

        DB::table('global.categorias_almacen')->insert($data);
        return redirect()->route('grupos.index')->with('success', 'Grupo registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        DB::table('global.categorias_almacen')->where('id_categoria', $id)->update($data);
        return redirect()->route('grupos.index')->with('success', 'Grupo actualizado exitosamente');
    }

    public function destroy($id)
    {
        $tieneProductos = DB::table('global.inventario')->where('id_categoria', $id)->exists();
        if ($tieneProductos) {
            return redirect()->route('grupos.index')->with('error', 'No se puede eliminar: grupo tiene productos asociados');
        }
        DB::table('global.categorias_almacen')->where('id_categoria', $id)->delete();
        return redirect()->route('grupos.index')->with('success', 'Grupo eliminado');
    }

    public function apiList()
    {
        $data = DB::table('global.categorias_almacen')
            ->select('global.categorias_almacen.*',
                DB::raw('(SELECT COUNT(*) FROM global.inventario WHERE global.inventario.id_categoria = global.categorias_almacen.id_categoria) as total_productos'))
            ->orderBy('nombre')
            ->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiShow($id)
    {
        $grupo = DB::table('global.categorias_almacen')->where('id_categoria', $id)->first();
        return response()->json(['success' => true, 'data' => $grupo]);
    }
}
