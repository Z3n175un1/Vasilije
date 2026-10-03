<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BancoController;
use App\Http\Controllers\ClasificadorController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\GastoGeneralController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TramoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehiculoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la aplicacion
|--------------------------------------------------------------------------
|
| Convenciones aplicadas en este archivo:
|
|  - `lectura`   : cualquier usuario autenticado, incluido solo-lectura.
|  - `escritura` : requiere capacidad de crear/editar (admin, supervisor,
|                  operador). El usuario de solo lectura NO entra.
|  - `borrado`   : requiere capacidad de eliminar (admin, supervisor).
|  - `admin`     : solo administrador.
|
| Las capacidades se resuelven en App\Policies\ModuloPolicy a partir del
| enum App\Enums\Rol, de modo que la matriz de permisos tiene un unico sitio.
|
*/

// =========================================================================
// AUTENTICACION (publica)
// =========================================================================
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login.post');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// =========================================================================
// AREA PROTEGIDA
// =========================================================================
Route::middleware('auth')->group(function (): void {

    // --- Lectura: cualquiera autenticado -------------------------------
    Route::middleware('lectura')->group(function (): void {

        // Panel
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::view('/documentos', 'documentos.index')->name('documentos.index');
        Route::get('/mantenimiento', [MantenimientoController::class, 'index'])->name('mantenimiento.index');

        // Unidades
        Route::get('/vehiculos', [VehiculoController::class, 'index'])->name('vehiculos.index');
        Route::get('/vehiculos/{id}/editar', [VehiculoController::class, 'edit'])->name('vehiculos.edit');

        // Personal
        Route::get('/personal', [PersonalController::class, 'index'])->name('personal.index');
        Route::get('/personal/{id}/editar', [PersonalController::class, 'edit'])->name('personal.edit');
        Route::get('/personal/{id}/sueldo', [PersonalController::class, 'sueldo'])->name('personal.sueldo');
        Route::get('/personal/{id}/viatico', [PersonalController::class, 'viatico'])->name('personal.viatico');

        // Almacen
        Route::get('/almacen', [AlmacenController::class, 'index'])->name('almacen.index');
        Route::get('/almacen/{id}/editar', [AlmacenController::class, 'edit'])->name('almacen.edit');

        // Compras y entregas en pagina propia (antes eran modales dentro del
        // listado). El `{tipo}` se valida en el controlador: solo COMPRA y
        // ENTREGA son validos, el resto redirige al listado con un error.
        Route::get('/almacen/movimiento/{tipo}', [AlmacenController::class, 'movimientoForm'])
            ->name('almacen.movimiento');
        Route::get('/almacen/movimiento/{tipo}/{id}', [AlmacenController::class, 'movimientoForm'])
            ->name('almacen.movimiento.editar');

        // Items y grupos
        Route::get('/items', [ItemController::class, 'index'])->name('items.index');
        Route::get('/items/{id}/editar', [ItemController::class, 'edit'])->name('items.edit');
        Route::get('/grupos', [GrupoController::class, 'index'])->name('grupos.index');
        Route::get('/grupos/{id}/editar', [GrupoController::class, 'edit'])->name('grupos.edit');

        // Rutas
        Route::get('/tramos', [TramoController::class, 'index'])->name('tramos.index');
        Route::get('/tramos/{id}/editar', [TramoController::class, 'edit'])->name('tramos.edit');

        // Facturacion
        Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
        Route::get('/facturacion/nuevo', [FacturacionController::class, 'create'])->name('facturacion.create');
        Route::get('/facturacion/{id}/editar', [FacturacionController::class, 'edit'])->name('facturacion.edit');

        // Gastos
        Route::get('/gastos', [GastoController::class, 'index'])->name('gastos.index');
        Route::get('/gastos/nuevo', [GastoController::class, 'create'])->name('gastos.create');
        Route::get('/gastos/crear', [GastoController::class, 'create'])->name('gastos.crear');
        Route::get('/gastos/{id}/editar', [GastoController::class, 'edit'])->name('gastos.edit');

        // Gastos generales
        Route::get('/gastos-generales', [GastoGeneralController::class, 'index'])->name('gastos-generales.index');
        Route::get('/gastos-generales/nuevo', [GastoGeneralController::class, 'create'])->name('gastos-generales.create');
        Route::get('/gastos-generales/{id}/editar', [GastoGeneralController::class, 'edit'])->name('gastos-generales.edit');

        // Bancos y proveedores
        Route::get('/bancos', [BancoController::class, 'index'])->name('bancos.index');
        Route::get('/bancos/nuevo', [BancoController::class, 'create'])->name('bancos.create');
        Route::get('/bancos/{id}/editar', [BancoController::class, 'edit'])->name('bancos.edit');
        Route::get('/bancos/{id}/estado', [BancoController::class, 'estado'])->name('bancos.estado');
        Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
        Route::get('/proveedores/nuevo', [ProveedorController::class, 'create'])->name('proveedores.create');
        Route::get('/proveedores/{id}/editar', [ProveedorController::class, 'edit'])->name('proveedores.edit');
        Route::get('/proveedores/{id}/estado', [ProveedorController::class, 'estado'])->name('proveedores.estado');

        // Reportes
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/pdf', [ReporteController::class, 'pdf'])->name('reportes.pdf');
        Route::get('/reportes/imprimir', [ReporteController::class, 'imprimir'])->name('reportes.imprimir');

        // Reporte PDF de una unidad concreta (modal del dashboard)
        Route::get('/reportes/unidad/{id}', function (int $id) {
            return redirect()->route('reportes.pdf', ['id_vehiculo' => $id]);
        })->name('reportes.unidad');
    });

    // --- Clasificador de gastos: lectura para todos, escritura admin -----
    Route::get('/clasificadores', [ClasificadorController::class, 'index'])->name('clasificadores.index');
    Route::get('/clasificadores/nuevo', [ClasificadorController::class, 'create'])
        ->middleware('admin')->name('clasificadores.create');
    Route::get('/clasificadores/{id}/editar', [ClasificadorController::class, 'edit'])
        ->middleware('admin')->name('clasificadores.edit');
    Route::post('/clasificadores', [ClasificadorController::class, 'store'])
        ->middleware('admin')->name('clasificadores.store');
    Route::put('/clasificadores/{id}', [ClasificadorController::class, 'update'])
        ->middleware('admin')->name('clasificadores.update');
    Route::delete('/clasificadores/{id}', [ClasificadorController::class, 'destroy'])
        ->middleware('admin')->name('clasificadores.destroy');

    // --- Escritura: requiere capacidad de crear/editar ------------------
    Route::middleware('escritura')->group(function (): void {

        // Unidades
        Route::get('/vehiculos/nuevo', [VehiculoController::class, 'create'])->name('vehiculos.create');
        Route::post('/vehiculos', [VehiculoController::class, 'store'])->name('vehiculos.store');
        Route::put('/vehiculos/{id}', [VehiculoController::class, 'update'])->name('vehiculos.update');

        // Personal
        Route::get('/personal/nuevo', [PersonalController::class, 'create'])->name('personal.create');
        Route::post('/personal', [PersonalController::class, 'store'])->name('personal.store');
        Route::put('/personal/{id}', [PersonalController::class, 'update'])->name('personal.update');
        Route::post('/personal/gasto', [PersonalController::class, 'storeGasto'])->name('personal.gasto.store');

        // Almacen
        Route::get('/almacen/nuevo', [AlmacenController::class, 'create'])->name('almacen.create');
        Route::post('/almacen', [AlmacenController::class, 'store'])->name('almacen.store');
        Route::put('/almacen/{id}', [AlmacenController::class, 'update'])->name('almacen.update');

        // Items y grupos
        Route::get('/items/nuevo', [ItemController::class, 'create'])->name('items.create');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::put('/items/{id}', [ItemController::class, 'update'])->name('items.update');
        Route::get('/grupos/nuevo', [GrupoController::class, 'create'])->name('grupos.create');
        Route::post('/grupos', [GrupoController::class, 'store'])->name('grupos.store');
        Route::put('/grupos/{id}', [GrupoController::class, 'update'])->name('grupos.update');

        // Rutas
        Route::get('/tramos/nuevo', [TramoController::class, 'create'])->name('tramos.create');
        Route::post('/tramos', [TramoController::class, 'store'])->name('tramos.store');
        Route::put('/tramos/{id}', [TramoController::class, 'update'])->name('tramos.update');

        // Facturacion
        Route::post('/facturacion', [FacturacionController::class, 'store'])->name('facturacion.store');
        Route::put('/facturacion/{id}', [FacturacionController::class, 'update'])->name('facturacion.update');

        // Gastos
        Route::post('/gastos', [GastoController::class, 'store'])->name('gastos.store');
        Route::put('/gastos/{id}', [GastoController::class, 'update'])->name('gastos.update');

        // Gastos generales
        Route::post('/gastos-generales', [GastoGeneralController::class, 'store'])->name('gastos-generales.store');
        Route::put('/gastos-generales/{id}', [GastoGeneralController::class, 'update'])->name('gastos-generales.update');

        // Bancos y proveedores
        Route::get('/bancos/nuevo', [BancoController::class, 'create'])->name('bancos.create');
        Route::post('/bancos', [BancoController::class, 'store'])->name('bancos.store');
        Route::put('/bancos/{id}', [BancoController::class, 'update'])->name('bancos.update');
        Route::get('/proveedores/nuevo', [ProveedorController::class, 'create'])->name('proveedores.create');
        Route::post('/proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::put('/proveedores/{id}', [ProveedorController::class, 'update'])->name('proveedores.update');
    });

    // --- Borrado: requiere capacidad de eliminar -------------------------
    Route::middleware('borrado')->group(function (): void {
        Route::delete('/vehiculos/{id}', [VehiculoController::class, 'destroy'])->name('vehiculos.destroy');
        Route::delete('/personal/{id}', [PersonalController::class, 'destroy'])->name('personal.destroy');
        Route::delete('/almacen/{id}', [AlmacenController::class, 'destroy'])->name('almacen.destroy');
        Route::delete('/items/{id}', [ItemController::class, 'destroy'])->name('items.destroy');
        Route::delete('/grupos/{id}', [GrupoController::class, 'destroy'])->name('grupos.destroy');
        Route::delete('/tramos/{id}', [TramoController::class, 'destroy'])->name('tramos.destroy');
        Route::delete('/bancos/{id}', [BancoController::class, 'destroy'])->name('bancos.destroy');
        Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
        Route::delete('/gastos-generales/{id}', [GastoGeneralController::class, 'destroy'])->name('gastos-generales.destroy');
    });

    // --- Anulacion: requiere admin o supervisor --------------------------
    Route::middleware('nivel:supervisor')->group(function (): void {
        Route::delete('/facturacion/{id}', [FacturacionController::class, 'destroy'])->name('facturacion.destroy');
        Route::delete('/gastos/{id}', [GastoController::class, 'destroy'])->name('gastos.destroy');
        Route::post('/api/vehiculos/vender', [VehiculoController::class, 'vender'])->name('vehiculos.vender');
    });

    // --- ADMINISTRACION --------------------------------------------------
    Route::middleware('admin')->prefix('usuarios')->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->name('usuarios.index');
        Route::get('/nuevo', [UserController::class, 'create'])->name('usuarios.create');
        Route::get('/{id}/editar', [UserController::class, 'edit'])->name('usuarios.edit');
        Route::post('/', [UserController::class, 'store'])->name('usuarios.store');
        Route::put('/{id}', [UserController::class, 'update'])->name('usuarios.update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    });

    Route::middleware('admin')->group(function (): void {
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::post('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
    });

    // =====================================================================
    // API INTERNA
    // =====================================================================
    // Comparte la sesion y la cookie CSRF del navegador: NO es una API
    // publica para terceros. Para eso haria falta un token (Sanctum) y el
    // grupo `auth:sanctum`.
    Route::prefix('api')->name('api.')->group(function (): void {

        // --- Lectura ---
        Route::get('/vehiculos', [VehiculoController::class, 'apiList']);
        Route::get('/vehiculos/{id}', [VehiculoController::class, 'apiShow']);
        Route::get('/gastos', [GastoController::class, 'apiList']);
        Route::get('/gastos/{id}', [GastoController::class, 'apiShow']);
        Route::get('/personal', [PersonalController::class, 'apiList']);
        Route::get('/personal/{id}', [PersonalController::class, 'apiShow']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/reportes/filtro', [ReporteController::class, 'filtro']);
        Route::get('/reportes/financiero', [ReporteController::class, 'financiero']);
        Route::get('/reportes/almacen', [ReporteController::class, 'almacen']);
        Route::get('/reportes/estadisticas', [ReporteController::class, 'estadisticas']);
        Route::get('/gastos-generales', [GastoGeneralController::class, 'apiList']);
        Route::get('/gastos-generales/{id}', [GastoGeneralController::class, 'apiShow']);
        Route::get('/bancos', [BancoController::class, 'apiList']);
        Route::get('/bancos/{id}', [BancoController::class, 'apiShow']);
        Route::get('/proveedores', [ProveedorController::class, 'apiList']);
        Route::get('/proveedores/{id}', [ProveedorController::class, 'apiShow']);
        Route::get('/tramos', [TramoController::class, 'apiList']);
        Route::get('/tramos/{id}', [TramoController::class, 'apiShow']);
        Route::get('/almacen', [AlmacenController::class, 'apiList']);
        Route::get('/almacen/categorias', [AlmacenController::class, 'apiCategorias']);
        Route::get('/almacen/next-code', [AlmacenController::class, 'apiNextCode']);
        Route::get('/almacen/movimientos', [AlmacenController::class, 'apiMovimientos']);
        Route::get('/lotes/ultimo', [AlmacenController::class, 'apiUltimoLote']);
        Route::get('/almacen/{id}', [AlmacenController::class, 'apiShow']);
        Route::get('/items', [ItemController::class, 'apiList']);
        Route::get('/items/{id}', [ItemController::class, 'apiShow']);
        Route::get('/grupos', [GrupoController::class, 'apiList']);
        Route::get('/grupos/{id}', [GrupoController::class, 'apiShow']);

        // Facturacion
        Route::get('/facturacion/listado', [FacturacionController::class, 'apiList']);
        Route::get('/facturacion/pendientes', [FacturacionController::class, 'apiPendientes']);
        Route::get('/facturacion/fletes/{numeroFactura}', [FacturacionController::class, 'apiFletesByFactura']);
        Route::get('/facturacion', [FacturacionController::class, 'apiAll']);
        Route::get('/facturacion/{id}', [FacturacionController::class, 'apiShow']);

        // Catalogos de consulta
        Route::get('/proveedores/activos', function () {
            return response()->json([
                'success' => true,
                'data' => \Illuminate\Support\Facades\DB::table('global.proveedores')
                    ->where('estado', 1)->orderBy('nombre_proveedor')->get(),
            ]);
        });

        // Clasificadores: ?alcance=unidad|general|todos
        Route::get('/clasificadores', [ClasificadorController::class, 'apiList']);
        Route::get('/clasificadores/{id}', [ClasificadorController::class, 'apiShow']);

        Route::get('/config', function (\App\Services\ConfiguracionService $config) {
            return response()->json(['success' => true, 'data' => $config->todo()]);
        });

        // --- Escritura ---
        Route::middleware('escritura')->group(function (): void {
            Route::post('/almacen/movimientos', [AlmacenController::class, 'apiGuardarMovimiento']);
            Route::put('/almacen/movimientos/{id}', [AlmacenController::class, 'apiActualizarMovimiento']);
            Route::delete('/almacen/movimientos/{id}', [AlmacenController::class, 'apiEliminarMovimiento']);
            Route::post('/facturacion/batch-facturar', [FacturacionController::class, 'apiBatchFacturar']);
            Route::put('/facturacion/cobrar', [FacturacionController::class, 'apiToggleCobrado']);
        });

        // --- Usuarios: solo admin (antesAnyone autenticado podia listarlos) ---
        Route::middleware('admin')->group(function (): void {
            Route::get('/usuarios', [UserController::class, 'apiList']);
        });
    });
});