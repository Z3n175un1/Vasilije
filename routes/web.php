<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\BancoController;
use App\Http\Controllers\TramoController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\FacturacionController;

// Auth routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Vehiculos
    Route::get('/vehiculos', [VehiculoController::class, 'index'])->name('vehiculos.index');
    Route::get('/vehiculos/nuevo', [VehiculoController::class, 'create'])->name('vehiculos.create');
    Route::get('/vehiculos/{id}/editar', [VehiculoController::class, 'edit'])->name('vehiculos.edit');
    Route::post('/vehiculos', [VehiculoController::class, 'store'])->name('vehiculos.store');
    Route::put('/vehiculos/{id}', [VehiculoController::class, 'update'])->name('vehiculos.update');
    Route::delete('/vehiculos/{id}', [VehiculoController::class, 'destroy'])->name('vehiculos.destroy');

    // Gastos
    Route::get('/gastos', [GastoController::class, 'index'])->name('gastos.index');
    Route::get('/gastos/nuevo', [GastoController::class, 'create'])->name('gastos.create');
    Route::get('/gastos/crear', [GastoController::class, 'create'])->name('gastos.crear');
    Route::get('/gastos/{id}/editar', [GastoController::class, 'edit'])->name('gastos.edit');
    Route::post('/gastos', [GastoController::class, 'store'])->name('gastos.store');
    Route::put('/gastos/{id}', [GastoController::class, 'update'])->name('gastos.update');
    Route::delete('/gastos/{id}', [GastoController::class, 'destroy'])->name('gastos.destroy');

    // Personal
    Route::get('/personal', [PersonalController::class, 'index'])->name('personal.index');
    Route::get('/personal/nuevo', [PersonalController::class, 'create'])->name('personal.create');
    Route::get('/personal/{id}/editar', [PersonalController::class, 'edit'])->name('personal.edit');
    Route::post('/personal', [PersonalController::class, 'store'])->name('personal.store');
    Route::put('/personal/{id}', [PersonalController::class, 'update'])->name('personal.update');
    Route::delete('/personal/{id}', [PersonalController::class, 'destroy'])->name('personal.destroy');

    // Almacen
    Route::get('/almacen', [AlmacenController::class, 'index'])->name('almacen.index');
    Route::get('/almacen/nuevo', [AlmacenController::class, 'create'])->name('almacen.create');
    Route::get('/almacen/{id}/editar', [AlmacenController::class, 'edit'])->name('almacen.edit');
    Route::post('/almacen', [AlmacenController::class, 'store'])->name('almacen.store');
    Route::put('/almacen/{id}', [AlmacenController::class, 'update'])->name('almacen.update');
    Route::delete('/almacen/{id}', [AlmacenController::class, 'destroy'])->name('almacen.destroy');

    // Tramos
    Route::get('/tramos', [TramoController::class, 'index'])->name('tramos.index');
    Route::get('/tramos/nuevo', [TramoController::class, 'create'])->name('tramos.create');
    Route::get('/tramos/{id}/editar', [TramoController::class, 'edit'])->name('tramos.edit');
    Route::post('/tramos', [TramoController::class, 'store'])->name('tramos.store');
    Route::put('/tramos/{id}', [TramoController::class, 'update'])->name('tramos.update');
    Route::delete('/tramos/{id}', [TramoController::class, 'destroy'])->name('tramos.destroy');

    // Facturacion
    Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
    Route::get('/facturacion/nuevo', [FacturacionController::class, 'create'])->name('facturacion.create');
    Route::get('/facturacion/{id}/editar', [FacturacionController::class, 'edit'])->name('facturacion.edit');
    Route::post('/facturacion', [FacturacionController::class, 'store'])->name('facturacion.store');
    Route::put('/facturacion/{id}', [FacturacionController::class, 'update'])->name('facturacion.update');
    Route::delete('/facturacion/{id}', [FacturacionController::class, 'destroy'])->name('facturacion.destroy');

    // Bancos
    Route::get('/bancos', [BancoController::class, 'index'])->name('bancos.index');
    Route::get('/bancos/nuevo', [BancoController::class, 'create'])->name('bancos.create');
    Route::get('/bancos/{id}/editar', [BancoController::class, 'edit'])->name('bancos.edit');
    Route::post('/bancos', [BancoController::class, 'store'])->name('bancos.store');
    Route::put('/bancos/{id}', [BancoController::class, 'update'])->name('bancos.update');
    Route::delete('/bancos/{id}', [BancoController::class, 'destroy'])->name('bancos.destroy');

    // Reportes
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/pdf', [ReporteController::class, 'pdf'])->name('reportes.pdf');

    // Documentos
    Route::view('/documentos', 'documentos.index')->name('documentos.index');

    // Items
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/nuevo', [ItemController::class, 'create'])->name('items.create');
    Route::get('/items/{id}/editar', [ItemController::class, 'edit'])->name('items.edit');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::put('/items/{id}', [ItemController::class, 'update'])->name('items.update');
    Route::delete('/items/{id}', [ItemController::class, 'destroy'])->name('items.destroy');

    // Grupos
    Route::get('/grupos', [GrupoController::class, 'index'])->name('grupos.index');
    Route::get('/grupos/nuevo', [GrupoController::class, 'create'])->name('grupos.create');
    Route::get('/grupos/{id}/editar', [GrupoController::class, 'edit'])->name('grupos.edit');
    Route::post('/grupos', [GrupoController::class, 'store'])->name('grupos.store');
    Route::put('/grupos/{id}', [GrupoController::class, 'update'])->name('grupos.update');
    Route::delete('/grupos/{id}', [GrupoController::class, 'destroy'])->name('grupos.destroy');

    // Configuracion
    Route::get('/configuracion', function () {
        $config = DB::table('global.configuracion')->pluck('valor', 'llave');
        return view('configuracion.index', ['config' => $config]);
    })->name('configuracion.index');
    Route::post('/configuracion', function (Request $request) {
        $data = $request->validate([
            'tipo_cambio' => 'required|numeric',
            'precio_tonelada_usd' => 'required|numeric',
        ]);
        DB::table('global.configuracion')->where('llave', 'tipo_cambio')->update(['valor' => $data['tipo_cambio']]);
        DB::table('global.configuracion')->where('llave', 'precio_tonelada_usd')->update(['valor' => $data['precio_tonelada_usd']]);
        return redirect()->route('configuracion.index')->with('success', 'Configuración actualizada exitosamente');
    })->name('configuracion.update');
});

// API routes (for AJAX calls)
Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/vehiculos', [VehiculoController::class, 'apiList']);
    Route::get('/vehiculos/{id}', [VehiculoController::class, 'apiShow']);
    Route::post('/vehiculos/vender', [VehiculoController::class, 'vender']);
    Route::get('/gastos', [GastoController::class, 'apiList']);
    Route::get('/gastos/{id}', [GastoController::class, 'apiShow']);
    Route::get('/personal', [PersonalController::class, 'apiList']);
    Route::get('/personal/{id}', [PersonalController::class, 'apiShow']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/reportes/filtro', [ReporteController::class, 'filtro']);
    Route::get('/reportes/financiero', [ReporteController::class, 'financiero']);
    Route::get('/reportes/almacen', [ReporteController::class, 'almacen']);
    Route::get('/bancos', [BancoController::class, 'apiList']);
    Route::get('/bancos/{id}', [BancoController::class, 'apiShow']);
    Route::get('/tramos', [TramoController::class, 'apiList']);
    Route::get('/tramos/{id}', [TramoController::class, 'apiShow']);
    Route::get('/almacen', [AlmacenController::class, 'apiList']);
    Route::get('/almacen/{id}', [AlmacenController::class, 'apiShow']);
    Route::get('/almacen/categorias', [AlmacenController::class, 'apiCategorias']);
    Route::get('/almacen/movimientos', [AlmacenController::class, 'apiMovimientos']);
    Route::get('/items', [ItemController::class, 'apiList']);
    Route::get('/items/{id}', [ItemController::class, 'apiShow']);
    Route::get('/grupos', [GrupoController::class, 'apiList']);
    Route::get('/grupos/{id}', [GrupoController::class, 'apiShow']);
    Route::get('/facturacion/listado', [FacturacionController::class, 'apiList']);
    Route::get('/facturacion/pendientes', [FacturacionController::class, 'apiPendientes']);
    Route::get('/facturacion/fletes/{numeroFactura}', [FacturacionController::class, 'apiFletesByFactura']);
    Route::post('/facturacion/batch-facturar', [FacturacionController::class, 'apiBatchFacturar']);
    Route::put('/facturacion/cobrar', [FacturacionController::class, 'apiToggleCobrado']);
    Route::get('/facturacion', [FacturacionController::class, 'apiAll']);
    Route::get('/facturacion/{id}', [FacturacionController::class, 'apiShow']);
    Route::get('/proveedores', function () {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.proveedores')->where('estado', 1)->orderBy('nombre_proveedor')->get()
        ]);
    });
    Route::get('/config', function () {
        $config = DB::table('global.configuracion')->pluck('valor', 'llave');
        return response()->json(['success' => true, 'data' => $config]);
    });
});
