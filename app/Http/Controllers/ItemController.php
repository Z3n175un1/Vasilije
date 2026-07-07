<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index()
    {
        return view('items.index');
    }

    public function create()
    {
        $categorias = DB::table('global.categorias_almacen')->orderBy('nombre')->get();
        return view('items.form', ['item' => null, 'categorias' => $categorias]);
    }

    public function edit($id)
    {
        $item = DB::table('global.inventario')->where('id_inventario', $id)->first();
        if (!$item) return redirect()->route('items.index')->with('error', 'Ítem no encontrado');
        $categorias = DB::table('global.categorias_almacen')->orderBy('nombre')->get();
        return view('items.form', ['item' => $item, 'categorias' => $categorias]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:20|unique:global.inventario,codigo',
            'nombre_producto' => 'required|string|max:100',
            'id_categoria' => 'nullable|integer|exists:global.categorias_almacen,id_categoria',
            'unidad_medida' => 'required|string|max:20',
            'stock_minimo' => 'nullable|numeric',
            'descripcion' => 'nullable|string',
        ]);
        $data['estado'] = 'ACTIVO';
        $data['stock_actual'] = 0;

        if (!empty($data['id_categoria'])) {
            $cat = DB::table('global.categorias_almacen')->where('id_categoria', $data['id_categoria'])->first();
            $data['categoria'] = $cat ? $cat->nombre : '';
        } else {
            $data['categoria'] = '';
        }

        DB::table('global.inventario')->insert($data);
        return redirect()->route('items.index')->with('success', 'Ítem registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:20|unique:global.inventario,codigo,' . $id . ',id_inventario',
            'nombre_producto' => 'required|string|max:100',
            'id_categoria' => 'nullable|integer|exists:global.categorias_almacen,id_categoria',
            'unidad_medida' => 'required|string|max:20',
            'stock_minimo' => 'nullable|numeric',
            'descripcion' => 'nullable|string',
        ]);

        if (!empty($data['id_categoria'])) {
            $cat = DB::table('global.categorias_almacen')->where('id_categoria', $data['id_categoria'])->first();
            $data['categoria'] = $cat ? $cat->nombre : '';
        } else {
            $data['categoria'] = '';
        }

        DB::table('global.inventario')->where('id_inventario', $id)->update($data);
        return redirect()->route('items.index')->with('success', 'Ítem actualizado exitosamente');
    }

    public function destroy($id)
    {
        DB::table('global.inventario')->where('id_inventario', $id)->update(['estado' => 'INACTIVO']);
        return redirect()->route('items.index')->with('success', 'Ítem desactivado');
    }

    public function apiList()
    {
        $data = DB::table('global.inventario')
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre_producto')
            ->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiShow($id)
    {
        $item = DB::table('global.inventario')->where('id_inventario', $id)->first();
        return response()->json(['success' => true, 'data' => $item]);
    }
}
