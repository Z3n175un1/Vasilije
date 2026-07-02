<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlmacenController extends Controller
{
    public function index()
    {
        return view('almacen.index');
    }

    public function create()
    {
        $categorias = DB::table('global.categorias_almacen')->orderBy('nombre')->get();
        $proveedores = DB::table('global.proveedores')->orderBy('nombre_proveedor')->get();
        return view('almacen.form', ['producto' => null, 'categorias' => $categorias, 'proveedores' => $proveedores]);
    }

    public function edit($id)
    {
        $producto = DB::table('global.inventario')->where('id_inventario', $id)->first();
        if (!$producto) return redirect()->route('almacen.index')->with('error', 'Producto no encontrado');
        $categorias = DB::table('global.categorias_almacen')->orderBy('nombre')->get();
        $proveedores = DB::table('global.proveedores')->orderBy('nombre_proveedor')->get();
        return view('almacen.form', ['producto' => $producto, 'categorias' => $categorias, 'proveedores' => $proveedores]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:20',
            'nombre_producto' => 'required|string|max:100',
            'categoria' => 'required|string|max:50',
            'unidad_medida' => 'required|string|max:20',
            'stock_actual' => 'required|numeric',
            'stock_minimo' => 'nullable|numeric',
            'precio_compra' => 'nullable|numeric',
            'ubicacion_almacen' => 'nullable|string|max:100',
            'id_proveedor' => 'nullable|integer',
            'marca' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
        ]);
        $data['estado'] = 'ACTIVO';

        $id = DB::table('global.inventario')->insertGetId($data);

        if ($data['stock_actual'] > 0) {
            $lote = 'LTE-' . str_pad($id, 5, '0', STR_PAD_LEFT);
            DB::table('global.lotes')->insert([
                'id_inventario' => $id,
                'codigo_lote' => $lote,
                'cantidad_inicial' => $data['stock_actual'],
                'cantidad_actual' => $data['stock_actual'],
                'precio_compra' => $data['precio_compra'] ?? 0,
                'estado' => 'ACTIVO',
            ]);
        }

        return redirect()->route('almacen.index')->with('success', 'Producto registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:20',
            'nombre_producto' => 'required|string|max:100',
            'categoria' => 'required|string|max:50',
            'unidad_medida' => 'required|string|max:20',
            'stock_actual' => 'required|numeric',
            'stock_minimo' => 'nullable|numeric',
            'precio_compra' => 'nullable|numeric',
            'ubicacion_almacen' => 'nullable|string|max:100',
            'id_proveedor' => 'nullable|integer',
            'marca' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
        ]);

        DB::table('global.inventario')->where('id_inventario', $id)->update($data);
        return redirect()->route('almacen.index')->with('success', 'Producto actualizado exitosamente');
    }

    public function destroy($id)
    {
        DB::table('global.inventario')->where('id_inventario', $id)->update(['estado' => 'INACTIVO']);
        return redirect()->route('almacen.index')->with('success', 'Producto desactivado');
    }

    public function apiList(Request $request)
    {
        $query = DB::table('global.inventario')
            ->leftJoin('global.proveedores', 'global.inventario.id_proveedor', '=', 'global.proveedores.id_proveedor')
            ->select('global.inventario.*', 'global.proveedores.nombre_proveedor')
            ->where('global.inventario.estado', 'ACTIVO');

        if ($request->filled('categoria')) {
            $query->where('global.inventario.categoria', $request->categoria);
        }
        if ($request->filled('busqueda')) {
            $query->where(function($q) use ($request) {
                $q->where('global.inventario.nombre_producto', 'like', '%' . $request->busqueda . '%')
                  ->orWhere('global.inventario.codigo', 'like', '%' . $request->busqueda . '%');
            });
        }

        $data = $query->orderBy('global.inventario.nombre_producto')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiShow($id)
    {
        $producto = DB::table('global.inventario')->where('id_inventario', $id)->first();
        return response()->json(['success' => true, 'data' => $producto]);
    }

    public function apiCategorias()
    {
        $data = DB::table('global.categorias_almacen')->orderBy('nombre')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiMovimientos(Request $request)
    {
        $query = DB::table('global.movimientos_inventario')
            ->leftJoin('global.inventario', 'global.movimientos_inventario.id_inventario', '=', 'global.inventario.id_inventario')
            ->leftJoin('global.vehiculos', 'global.movimientos_inventario.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->select('global.movimientos_inventario.*', 'global.inventario.nombre_producto', 'global.vehiculos.placa_vehiculo')
            ->orderBy('global.movimientos_inventario.fecha_movimiento', 'desc');

        if ($request->filled('id_inventario')) {
            $query->where('global.movimientos_inventario.id_inventario', $request->id_inventario);
        }

        $data = $query->limit(50)->get();
        return response()->json(['success' => true, 'data' => $data]);
    }
}
