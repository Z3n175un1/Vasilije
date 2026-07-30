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
        return view('almacen.form', [
            'producto' => null,
            'categorias' => $categorias,
            'proveedores' => $proveedores,
        ]);
    }

    public function edit($id)
    {
        $producto = DB::table('global.inventario')->where('id_inventario', $id)->first();
        if (!$producto) return redirect()->route('almacen.index')->with('error', 'Producto no encontrado');
        $categorias = DB::table('global.categorias_almacen')->orderBy('nombre')->get();
        $proveedores = DB::table('global.proveedores')->orderBy('nombre_proveedor')->get();
        return view('almacen.form', [
            'producto' => $producto,
            'categorias' => $categorias,
            'proveedores' => $proveedores,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:20|unique:inventario,codigo',
            'nombre_producto' => 'required|string|max:100',
            'id_categoria' => 'nullable|integer|exists:categorias_almacen,id_categoria',
            'unidad_medida' => 'required|string|max:20',
            'stock_actual' => 'nullable|numeric',
            'stock_minimo' => 'nullable|numeric',
            'precio_compra' => 'nullable|numeric',
            'id_proveedor' => 'nullable|integer',
            'marca' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'codigo_barras' => 'nullable|string|max:50',
        ]);
        $data['estado'] = 'ACTIVO';
        if (!isset($data['stock_actual'])) $data['stock_actual'] = 0;

        if (!empty($data['id_categoria'])) {
            $cat = DB::table('global.categorias_almacen')->where('id_categoria', $data['id_categoria'])->first();
            $data['categoria'] = $cat ? $cat->nombre : '';
        } else {
            $data['categoria'] = '';
        }

        DB::table('global.inventario')->insertGetId($data, 'id_inventario');

        return redirect()->route('almacen.index')->with('success', 'Producto registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:20|unique:inventario,codigo,' . $id . ',id_inventario',
            'nombre_producto' => 'required|string|max:100',
            'id_categoria' => 'nullable|integer|exists:categorias_almacen,id_categoria',
            'unidad_medida' => 'required|string|max:20',
            'stock_actual' => 'nullable|numeric',
            'stock_minimo' => 'nullable|numeric',
            'precio_compra' => 'nullable|numeric',
            'id_proveedor' => 'nullable|integer',
            'marca' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'codigo_barras' => 'nullable|string|max:50',
        ]);

        if (!empty($data['id_categoria'])) {
            $cat = DB::table('global.categorias_almacen')->where('id_categoria', $data['id_categoria'])->first();
            $data['categoria'] = $cat ? $cat->nombre : '';
        } else {
            $data['categoria'] = '';
        }

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
            ->select('global.inventario.*', 'global.proveedores.nombre_proveedor',
                DB::raw('(SELECT codigo_lote FROM global.lotes WHERE id_inventario = global.inventario.id_inventario ORDER BY id_lote DESC LIMIT 1) as codigo_lote'))
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
            ->leftJoin('global.personal', 'global.movimientos_inventario.id_personal', '=', 'global.personal.id_personal')
            ->leftJoin('global.lotes', 'global.movimientos_inventario.id_lote', '=', 'global.lotes.id_lote')
            ->select(
                'global.movimientos_inventario.*',
                'global.inventario.nombre_producto',
                'global.vehiculos.placa_vehiculo',
                DB::raw("COALESCE(NULLIF(global.vehiculos.conductor, ''), CONCAT(global.personal.nombres, ' ', global.personal.apellidos)) as conductor"),
                'global.lotes.codigo_lote'
            )
            ->orderBy('global.movimientos_inventario.fecha_movimiento', 'desc');

        if ($request->filled('id_inventario')) {
            $query->where('global.movimientos_inventario.id_inventario', $request->id_inventario);
        }
        if ($request->filled('tipo')) {
            $query->where('global.movimientos_inventario.tipo_movimiento', $request->tipo);
        }

        $data = $query->limit(50)->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiGuardarMovimiento(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_inventario' => 'required|integer',
                'tipo_movimiento' => 'required|string|in:COMPRA,SALIDA',
                'cantidad' => 'required|numeric|min:0.01',
                'fecha_movimiento' => 'nullable|date',
                'proveedor' => 'nullable|string|max:200',
                'precio_unitario' => 'nullable|numeric',
                'precio_compra' => 'nullable|numeric',
                'codigo_lote' => 'nullable|string|max:50',
                'id_vehiculo' => 'nullable|integer',
                'id_personal' => 'nullable|integer',
                'observaciones' => 'nullable|string',
            ]);

            $movId = DB::table('global.movimientos_inventario')->insertGetId([
                'id_inventario' => $validated['id_inventario'],
                'tipo_movimiento' => $validated['tipo_movimiento'],
                'cantidad' => $validated['cantidad'],
                'fecha_movimiento' => $validated['fecha_movimiento'] ?? date('Y-m-d'),
                'costo_unitario' => $validated['precio_unitario'] ?? null,
                'proveedor' => $validated['proveedor'] ?? null,
                'id_vehiculo' => $validated['id_vehiculo'] ?? null,
                'id_personal' => $validated['id_personal'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ], 'id_movimiento');

            if ($validated['tipo_movimiento'] === 'COMPRA' && !empty($validated['codigo_lote'])) {
                $loteExistente = DB::table('global.lotes')
                    ->where('codigo_lote', $validated['codigo_lote'])
                    ->where('id_inventario', $validated['id_inventario'])
                    ->first();
                if ($loteExistente) {
                    DB::table('global.lotes')
                        ->where('id_lote', $loteExistente->id_lote)
                        ->update([
                            'cantidad_actual' => DB::raw('cantidad_actual + ' . $validated['cantidad']),
                            'precio_compra' => $validated['precio_compra'] ?? $loteExistente->precio_compra,
                        ]);
                } else {
                    $loteId = DB::table('global.lotes')->insertGetId([
                        'id_inventario' => $validated['id_inventario'],
                        'codigo_lote' => $validated['codigo_lote'],
                        'fecha_ingreso' => $validated['fecha_movimiento'] ?? date('Y-m-d'),
                        'cantidad_inicial' => $validated['cantidad'],
                        'cantidad_actual' => $validated['cantidad'],
                        'precio_compra' => $validated['precio_compra'] ?? 0,
                        'estado' => 'ACTIVO',
                    ], 'id_lote');
                    DB::table('global.movimientos_inventario')
                        ->where('id_movimiento', $movId)
                        ->update(['id_lote' => $loteId]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Movimiento registrado']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiUltimoLote()
    {
        $lote = DB::table('global.lotes')->orderBy('id_lote', 'desc')->first();
        return response()->json(['success' => true, 'data' => $lote]);
    }
}
