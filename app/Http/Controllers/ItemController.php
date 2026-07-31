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
            'codigo' => 'nullable|string|max:20|unique:inventario,codigo',
            'nombre_producto' => 'required|string|max:100',
            'id_categoria' => 'nullable|integer|exists:categorias_almacen,id_categoria',
            'unidad_medida' => 'required|string|max:20',
            'stock_minimo' => 'nullable|numeric',
            'stock_actual' => 'nullable|numeric',
            'descripcion' => 'nullable|string',
            'codigo_barras' => 'nullable|string|max:50',
        ]);
        $data['estado'] = 'ACTIVO';
        if (!isset($data['stock_actual'])) $data['stock_actual'] = 0;

        if (empty($data['codigo'])) {
            $catName = '';
            if (!empty($data['id_categoria'])) {
                $cat = DB::table('global.categorias_almacen')->where('id_categoria', $data['id_categoria'])->first();
                $catName = $cat ? $cat->nombre : '';
            }
            $data['codigo'] = $this->generateNextCode($catName, $data['id_categoria'] ?? null);
        }

        DB::table('global.inventario')->insert($data);
        return redirect()->route('items.index')->with('success', 'Ítem registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:20|unique:inventario,codigo,' . $id . ',id_inventario',
            'nombre_producto' => 'required|string|max:100',
            'id_categoria' => 'nullable|integer|exists:categorias_almacen,id_categoria',
            'unidad_medida' => 'required|string|max:20',
            'stock_minimo' => 'nullable|numeric',
            'stock_actual' => 'nullable|numeric',
            'descripcion' => 'nullable|string',
            'codigo_barras' => 'nullable|string|max:50',
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
            ->leftJoin('global.categorias_almacen', 'global.inventario.id_categoria', '=', 'global.categorias_almacen.id_categoria')
            ->select('global.inventario.*', 'global.categorias_almacen.nombre as categoria')
            ->where('global.inventario.estado', 'ACTIVO')
            ->orderBy('global.inventario.nombre_producto')
            ->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiShow($id)
    {
        $item = DB::table('global.inventario')
            ->leftJoin('global.categorias_almacen', 'global.inventario.id_categoria', '=', 'global.categorias_almacen.id_categoria')
            ->select('global.inventario.*', 'global.categorias_almacen.nombre as categoria')
            ->where('global.inventario.id_inventario', $id)
            ->first();
        return response()->json(['success' => true, 'data' => $item]);
    }

    private function generateNextCode($categoryName, $categoryId = null)
    {
        $prefix = $categoryName ? strtoupper(substr($categoryName, 0, 2)) : 'XX';

        $count = DB::table('global.inventario')
            ->where('id_categoria', $categoryId)
            ->count();

        $itemNum = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        $lastProduct = DB::table('global.inventario')
            ->where('codigo', 'like', $prefix . '-%')
            ->orderBy('id_inventario', 'desc')
            ->first();

        $lastSeq = 0;
        if ($lastProduct) {
            $parts = explode('-', $lastProduct->codigo);
            if (count($parts) === 3) {
                $lastSeq = (int) $parts[2];
            }
        }
        $seqNum = str_pad($lastSeq + 1, 5, '0', STR_PAD_LEFT);

        return $prefix . '-' . $itemNum . '-' . $seqNum;
    }
}
