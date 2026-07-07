<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TramoController extends Controller
{
    public function index()
    {
        return view('tramos.index');
    }

    public function create()
    {
        $precioTonUsd = DB::table('global.configuracion')->where('llave', 'precio_tonelada_usd')->value('valor') ?? 13;
        return view('tramos.form', ['tramo' => null, 'precioTonUsd' => $precioTonUsd]);
    }

    public function edit($id)
    {
        $tramo = DB::table('global.tramos')->where('id_tramo', $id)->first();
        if (!$tramo) return redirect()->route('tramos.index')->with('error', 'Tramo no encontrado');
        $precioTonUsd = DB::table('global.configuracion')->where('llave', 'precio_tonelada_usd')->value('valor') ?? 13;
        return view('tramos.form', ['tramo' => $tramo, 'precioTonUsd' => $precioTonUsd]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'origen' => 'required|string|max:200',
            'destino' => 'required|string|max:200',
            'kilometros' => 'required|numeric',
            'precio_total' => 'required|numeric',
            'precio_dolar_tonelada' => 'nullable|numeric',
            'gasolina_promedio' => 'nullable|numeric',
            'diesel_promedio' => 'nullable|numeric',
            'gas_promedio' => 'nullable|numeric',
        ]);

        DB::table('global.tramos')->insert($data);
        return redirect()->route('tramos.index')->with('success', 'Tramo registrado exitosamente');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'origen' => 'required|string|max:200',
            'destino' => 'required|string|max:200',
            'kilometros' => 'required|numeric',
            'precio_total' => 'required|numeric',
            'precio_dolar_tonelada' => 'nullable|numeric',
            'gasolina_promedio' => 'nullable|numeric',
            'diesel_promedio' => 'nullable|numeric',
            'gas_promedio' => 'nullable|numeric',
        ]);

        DB::table('global.tramos')->where('id_tramo', $id)->update($data);
        return redirect()->route('tramos.index')->with('success', 'Tramo actualizado exitosamente');
    }

    public function destroy($id)
    {
        DB::table('global.tramos')->where('id_tramo', $id)->delete();
        return redirect()->route('tramos.index')->with('success', 'Tramo eliminado');
    }

    public function apiList()
    {
        $data = DB::table('global.tramos')->orderBy('origen')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function apiShow($id)
    {
        $tramo = DB::table('global.tramos')->where('id_tramo', $id)->first();
        return response()->json(['success' => true, 'data' => $tramo]);
    }
}
