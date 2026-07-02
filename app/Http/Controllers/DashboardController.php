<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function stats()
    {
        $vehiculos = DB::table('global.vehiculos')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) as inactivos,
                SUM(CASE WHEN estado = 2 THEN 1 ELSE 0 END) as en_taller
            ")
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'vehiculos' => $vehiculos,
            ]
        ]);
    }
}
