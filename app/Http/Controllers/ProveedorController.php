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

    public function estado($id)
    {
        $proveedor = DB::table('global.proveedores')->where('id_proveedor', $id)->first();
        if (!$proveedor) return response()->json(['error' => 'Proveedor no encontrado'], 404);

        $gastos = DB::table('global.gastos')
            ->where('global.gastos.id_proveedor', $id)
            ->select('global.gastos.id_gasto as id', 'global.gastos.fecha_gasto as fecha', 'global.gastos.concepto',
                'global.gastos.monto', 'global.gastos.condicion_pago', 'global.gastos.nro_documento',
                'global.gastos.id_proveedor')
            ->get()
            ->map(fn($g) => ['id' => $g->id, 'tipo' => 'GASTO', 'fecha' => $g->fecha, 'concepto' => $g->concepto,
                'proveedor' => $proveedor->nombre_proveedor, 'monto' => $g->monto, 'nro_documento' => $g->nro_documento, 'condicion_pago' => $g->condicion_pago]);

        $movimientos = DB::table('global.movimientos_inventario')
            ->where('global.movimientos_inventario.id_proveedor', $id)
            ->select('global.movimientos_inventario.id_movimiento as id', 'global.movimientos_inventario.fecha_movimiento as fecha',
                'global.movimientos_inventario.costo_total as monto', 'global.movimientos_inventario.condicion_pago',
                'global.movimientos_inventario.documento_numero as nro_documento',
                'global.movimientos_inventario.id_proveedor')
            ->get()
            ->map(fn($m) => ['id' => $m->id, 'tipo' => 'MOVIMIENTO', 'fecha' => $m->fecha, 'concepto' => $m->monto,
                'proveedor' => $proveedor->nombre_proveedor, 'monto' => $m->monto, 'nro_documento' => $m->nro_documento, 'condicion_pago' => $m->condicion_pago]);

        $allMovimientos = $gastos->concat($movimientos)->sortBy('fecha')->values();

        $saldo = (float) $proveedor->saldo_inicial ?? 0;
        $allMovimientos = $allMovimientos->map(function ($m) use (&$saldo) {
            $monto = (float) $m['monto'];
            $saldo -= $monto;
            $m['saldo'] = $saldo;
            return $m;
        });

        $totalDebitos = $allMovimientos->sum('monto');
        $saldoActual = (float) $proveedor->saldo_inicial - $totalDebitos;

        return response()->json([
            'success' => true,
            'proveedor' => $proveedor,
            'movimientos' => $allMovimientos,
            'totalDebitos' => $totalDebitos,
            'saldoActual' => $saldoActual
        ]);
    }
}
