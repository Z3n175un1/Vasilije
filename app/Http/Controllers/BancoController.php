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

        DB::table('global.bancos')->where('id_banco', $id)->update($data);
        return redirect()->route('bancos.index')->with('success', 'Banco actualizado exitosamente');
    }

    public function destroy($id)
    {
        DB::table('global.bancos')->where('id_banco', $id)->update(['estado' => 'INACTIVO']);
        return redirect()->route('bancos.index')->with('success', 'Banco desactivado');
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
