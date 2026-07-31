<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BancoController extends Controller
{
    public function index()
    {
        return view('bancos.index');
    }

    public function create()
    {
        return view('bancos.form', ['banco' => null]);
    }

    public function edit($id)
    {
        $banco = DB::table('global.bancos')->where('id_banco', $id)->first();
        if (!$banco) return redirect()->route('bancos.index')->with('error', 'Banco no encontrado');
        return view('bancos.form', ['banco' => $banco]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_banco' => 'required|string|max:200',
            'numero_cuenta' => 'required|string|max:100',
            'titular' => 'required|string|max:200',
            'tipo_cuenta' => 'required|string|max:50',
            'moneda' => 'required|string|max:10',
            'saldo_inicial' => 'required|numeric',
        ]);
        $data['saldo_actual'] = $data['saldo_inicial'];
        $data['estado'] = 'ACTIVO';

        DB::table('global.bancos')->insert($data);
        return redirect()->route('bancos.index')->with('success', 'Banco registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre_banco' => 'required|string|max:200',
            'numero_cuenta' => 'required|string|max:100',
            'titular' => 'required|string|max:200',
            'tipo_cuenta' => 'required|string|max:50',
            'moneda' => 'required|string|max:10',
            'saldo_inicial' => 'required|numeric',
        ]);

        $banco = DB::table('global.bancos')->where('id_banco', $id)->first();
        $diferencia = $data['saldo_inicial'] - $banco->saldo_inicial;
        $data['saldo_actual'] = $banco->saldo_actual + $diferencia;

        DB::table('global.bancos')->where('id_banco', $id)->update($data);
        return redirect()->route('bancos.index')->with('success', 'Banco actualizado exitosamente');
    }

    public function destroy($id)
    {
        DB::table('global.bancos')->where('id_banco', $id)->update(['estado' => 'INACTIVO']);
        return redirect()->route('bancos.index')->with('success', 'Banco desactivado');
    }

    public function estado($id)
    {
        $banco = DB::table('global.bancos')->where('id_banco', $id)->first();
        if (!$banco) return redirect()->route('bancos.index')->with('error', 'Banco no encontrado');

        $movimientos = collect();

        $gastos = DB::table('global.gastos')
            ->leftJoin('global.vehiculos', 'global.gastos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->leftJoin('global.proveedores', 'global.gastos.id_proveedor', '=', 'global.proveedores.id_proveedor')
            ->where('global.gastos.id_banco', $id)
            ->where('global.gastos.condicion_pago', 'CONTADO')
            ->select('global.gastos.id_gasto as id', 'global.gastos.fecha_gasto as fecha', 'global.gastos.concepto',
                'global.gastos.monto', 'global.gastos.condicion_pago', 'global.gastos.nro_documento',
                'global.vehiculos.placa_vehiculo', 'global.proveedores.nombre_proveedor as proveedor')
            ->get()
            ->map(fn($g) => ['id' => $g->id, 'tipo' => 'GASTO', 'fecha' => $g->fecha, 'concepto' => $g->concepto,
                'proveedor' => $g->proveedor, 'monto' => $g->monto, 'nro_documento' => $g->nro_documento, 'condicion_pago' => $g->condicion_pago]);

        $compras = DB::table('global.movimientos_inventario')
            ->leftJoin('global.inventario', 'global.movimientos_inventario.id_inventario', '=', 'global.inventario.id_inventario')
            ->leftJoin('global.proveedores', 'global.movimientos_inventario.id_proveedor', '=', 'global.proveedores.id_proveedor')
            ->where('global.movimientos_inventario.id_banco', $id)
            ->where('global.movimientos_inventario.tipo_movimiento', 'COMPRA')
            ->where('global.movimientos_inventario.condicion_pago', 'CONTADO')
            ->select('global.movimientos_inventario.id_movimiento as id', 'global.movimientos_inventario.fecha_movimiento as fecha',
                'global.inventario.nombre_producto as concepto', 'global.movimientos_inventario.costo_total as monto',
                'global.movimientos_inventario.condicion_pago', 'global.movimientos_inventario.documento_numero as nro_documento',
                'global.proveedores.nombre_proveedor as proveedor')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'tipo' => 'COMPRA', 'fecha' => $c->fecha, 'concepto' => $c->concepto,
                'proveedor' => $c->proveedor, 'monto' => $c->monto, 'nro_documento' => $c->nro_documento, 'condicion_pago' => $c->condicion_pago]);

        $movimientos = $gastos->concat($compras)->sortBy('fecha')->values();

        $saldo = (float) $banco->saldo_inicial;
        $movimientos = $movimientos->map(function ($m) use (&$saldo) {
            $monto = (float) $m['monto'];
            $saldo -= $monto;
            $m['saldo'] = $saldo;
            return $m;
        });

        $totalDebitos = $movimientos->sum('monto');
        $saldoActual = (float) $banco->saldo_inicial - (float) $totalDebitos;

        return view('bancos.estado', compact('banco', 'movimientos', 'totalDebitos', 'saldoActual'));
    }

    public function apiList()
    {
        $data = DB::table('global.bancos')->orderBy('nombre_banco')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiShow($id)
    {
        $banco = DB::table('global.bancos')->where('id_banco', $id)->first();
        return response()->json(['success' => true, 'data' => $banco]);
    }
}
