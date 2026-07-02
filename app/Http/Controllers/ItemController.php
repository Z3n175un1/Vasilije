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
            'codigo' => 'required|string|max:20',
            'nombre_producto' => 'required|string|max:100',
            'categoria' => 'required|string|max:50',
            'unidad_medida' => 'required|string|max:20',
            'precio_compra' => 'nullable|numeric',
            'precio_venta' => 'nullable|numeric',
            'marca' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'stock_actual' => 'required|numeric',
            'stock_minimo' => 'nullable|numeric',
        ]);
        $data['estado'] = 'ACTIVO';

        DB::table('global.inventario')->insert($data);
        return redirect()->route('items.index')->with('success', 'Ítem registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:20',
            'nombre_producto' => 'required|string|max:100',
            'categoria' => 'required|string|max:50',
            'unidad_medida' => 'required|string|max:20',
            'precio_compra' => 'nullable|numeric',
            'precio_venta' => 'nullable|numeric',
            'marca' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'stock_actual' => 'required|numeric',
            'stock_minimo' => 'nullable|numeric',
        ]);

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
