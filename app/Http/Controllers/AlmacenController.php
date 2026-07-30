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
        $ultimoLote = DB::table('global.lotes')->orderBy('id_lote', 'desc')->first();
        $nuevoNumero = $ultimoLote ? intval(substr($ultimoLote->codigo_lote, 3)) + 1 : 1;
        $nuevoCodigoLote = 'LO-' . str_pad($nuevoNumero, 6, '0', STR_PAD_LEFT);
        return view('almacen.form', [
            'producto' => null,
            'categorias' => $categorias,
            'proveedores' => $proveedores,
            'nuevoCodigoLote' => $nuevoCodigoLote,
        ]);
    }

    public function edit($id)
    {
        $producto = DB::table('global.inventario')->where('id_inventario', $id)->first();
        if (!$producto) return redirect()->route('almacen.index')->with('error', 'Producto no encontrado');
        $categorias = DB::table('global.categorias_almacen')->orderBy('nombre')->get();
        $proveedores = DB::table('global.proveedores')->orderBy('nombre_proveedor')->get();
        $lote = DB::table('global.lotes')->where('id_inventario', $id)->orderBy('id_lote', 'desc')->first();
        $producto->codigo_lote = $lote ? $lote->codigo_lote : '';
        return view('almacen.form', [
            'producto' => $producto,
            'categorias' => $categorias,
            'proveedores' => $proveedores,
            'nuevoCodigoLote' => '',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:20|unique:inventario,codigo',
            'nombre_producto' => 'required|string|max:100',
            'id_categoria' => 'nullable|integer|exists:categorias_almacen,id_categoria',
            'unidad_medida' => 'required|string|max:20',
            'stock_actual' => 'required|numeric',
            'stock_minimo' => 'nullable|numeric',
            'precio_compra' => 'nullable|numeric',
            'precio_venta' => 'nullable|numeric',
            'id_proveedor' => 'nullable|integer',
            'marca' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'codigo_barras' => 'nullable|string|max:50',
            'codigo_lote' => 'nullable|string|max:50',
            'fecha_ingreso' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
        ]);
        $data['estado'] = 'ACTIVO';
        $codigoLote = $data['codigo_lote'] ?? null;
        unset($data['codigo_lote']);

        if (!empty($data['id_categoria'])) {
            $cat = DB::table('global.categorias_almacen')->where('id_categoria', $data['id_categoria'])->first();
            $data['categoria'] = $cat ? $cat->nombre : '';
        } else {
            $data['categoria'] = '';
        }

        $id = DB::table('global.inventario')->insertGetId($data, 'id_inventario');

        if (floatval($data['stock_actual']) > 0) {
            $codigoLoteFinal = $codigoLote ?? ('LO-' . str_pad($id, 6, '0', STR_PAD_LEFT));
            DB::table('global.lotes')->insert([
                'id_inventario' => $id,
                'codigo_lote' => $codigoLoteFinal,
                'fecha_ingreso' => $data['fecha_ingreso'] ?? date('Y-m-d'),
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
            'codigo' => 'required|string|max:20|unique:inventario,codigo,' . $id . ',id_inventario',
            'nombre_producto' => 'required|string|max:100',
            'id_categoria' => 'nullable|integer|exists:categorias_almacen,id_categoria',
            'unidad_medida' => 'required|string|max:20',
            'stock_actual' => 'required|numeric',
            'stock_minimo' => 'nullable|numeric',
            'precio_compra' => 'nullable|numeric',
            'precio_venta' => 'nullable|numeric',
            'id_proveedor' => 'nullable|integer',
            'marca' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string',
            'codigo_barras' => 'nullable|string|max:50',
            'fecha_ingreso' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
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
            ->select(
                'global.movimientos_inventario.*',
                'global.inventario.nombre_producto',
                'global.vehiculos.placa_vehiculo',
                DB::raw("COALESCE(NULLIF(global.vehiculos.conductor, ''), CONCAT(global.personal.nombres, ' ', global.personal.apellidos)) as conductor")
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
                'id_vehiculo' => 'nullable|integer',
                'id_personal' => 'nullable|integer',
                'observaciones' => 'nullable|string',
            ]);

            DB::table('global.movimientos_inventario')->insert([
                'id_inventario' => $validated['id_inventario'],
                'tipo_movimiento' => $validated['tipo_movimiento'],
                'cantidad' => $validated['cantidad'],
                'fecha_movimiento' => $validated['fecha_movimiento'] ?? date('Y-m-d'),
                'proveedor' => $validated['proveedor'] ?? null,
                'id_vehiculo' => $validated['id_vehiculo'] ?? null,
                'id_personal' => $validated['id_personal'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            return response()->json(['success' => true, 'message' => 'Movimiento registrado']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
