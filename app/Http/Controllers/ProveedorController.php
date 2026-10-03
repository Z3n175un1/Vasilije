<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProveedorRequest;
use App\Services\AuditoriaService;
use App\Services\ConfiguracionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * PROVEEDORES y su estado de cuenta.
 *
 * CAMBIOS RESPECTO A LA VERSION ANTERIOR
 * --------------------------------------
 *  1. `estado()` leia `proveedores.saldo_inicial`, columna que NO existe:
 *     la consulta reventaba con "column does not exist" y el metodo no
 *     estaba enrutado, asi que el bug llevaba tiempo latente.
 *     El saldo de arranque se toma ahora de `ConfiguracionService`
 *     (llave `saldo_inicial_proveedores`, 0 si no esta definida).
 *  2. `destroy()` hacia DELETE fisico dejando gastos huerfanos con
 *     id_proveedor. Ahora se desactiva, y solo se borra si no hay gastos.
 */
class ProveedorController extends Controller
{
    public function __construct(
        private readonly ConfiguracionService $config,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(): View
    {
        return view('proveedores.index');
    }

    public function create(): View
    {
        return view('proveedores.form', ['proveedor' => null]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $proveedor = DB::table('global.proveedores')->where('id_proveedor', $id)->first();

        if (!$proveedor) {
            return redirect()->route('proveedores.index')->with('error', 'Proveedor no encontrado.');
        }

        return view('proveedores.form', ['proveedor' => $proveedor]);
    }

    public function store(StoreProveedorRequest $request): RedirectResponse
    {
        $id = DB::table('global.proveedores')->insertGetId($request->datosNormalizados(), 'id_proveedor');

        $this->auditoria->registrar('PROVEEDOR_CREADO', 'proveedores', "Proveedor #{$id} creado", ['id' => $id]);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado exitosamente');
    }

    public function update(StoreProveedorRequest $request, string $id): RedirectResponse
    {
        $datos = $request->datosNormalizados();
        unset($datos['estado']);

        DB::table('global.proveedores')->where('id_proveedor', $id)->update($datos);

        $this->auditoria->registrar('PROVEEDOR_ACTUALIZADO', 'proveedores', "Proveedor #{$id} actualizado", ['id' => $id]);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado exitosamente');
    }

    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        if (!$request->user()?->can('eliminar', 'proveedores')) {
            abort(403, 'Su rol no permite eliminar proveedores.');
        }

        // Con gastos o compras asociadas solo se desactiva: borrarlo dejaria
        // documentos sin proveedor y romperia el estado de cuenta.
        $tieneMovimientos = DB::table('global.gastos')->where('id_proveedor', $id)->exists()
            || DB::table('global.movimientos_inventario')->where('id_proveedor', $id)->exists()
            || DB::table('global.gastos_generales')->where('id_proveedor', $id)->exists();

        if ($tieneMovimientos) {
            DB::table('global.proveedores')->where('id_proveedor', $id)->update(['estado' => 0]);

            return $this->respuesta($request, 'Proveedor desactivado (tiene movimientos asociados).');
        }

        DB::table('global.proveedores')->where('id_proveedor', $id)->delete();

        $this->auditoria->registrar('PROVEEDOR_ELIMINADO', 'proveedores', "Proveedor #{$id} eliminado", ['id' => $id]);

        return $this->respuesta($request, 'Proveedor eliminado');
    }

    public function apiList(Request $request): JsonResponse
    {
        $query = DB::table('global.proveedores');

        if ($request->filled('busqueda')) {
            $q = $request->string('busqueda')->value();

            $query->where(function ($w) use ($q) {
                $w->where('nombre_proveedor', 'ilike', "%{$q}%")
                    ->orWhere('nit_ci', 'ilike', "%{$q}%")
                    ->orWhere('contacto', 'ilike', "%{$q}%");
            });
        }

        match ($request->query('orden', 'abc')) {
            'z_a' => $query->orderByDesc('nombre_proveedor'),
            'ultimo' => $query->orderByDesc('id_proveedor'),
            default => $query->orderBy('nombre_proveedor'),
        };

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function apiShow(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.proveedores')->where('id_proveedor', $id)->first(),
        ]);
    }

    /**
     * Estado de cuenta: deuda acumulada por gastos y compras a credito.
     *
     * El saldo se DERIVA, no se almacena: `proveedores` no tiene columna
     * `saldo_inicial`. El arranque sale de la configuracion global.
     */
    public function estado(string $id): JsonResponse
    {
        $proveedor = DB::table('global.proveedores')->where('id_proveedor', $id)->first();

        if (!$proveedor) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado.'], 404);
        }

        $gastos = DB::table('global.gastos')
            ->where('id_proveedor', $id)
            ->select('id_gasto as id', 'fecha_gasto as fecha', 'concepto', 'monto', 'condicion_pago', 'nro_documento')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'tipo' => 'GASTO',
                'fecha' => $g->fecha,
                'concepto' => $g->concepto,
                'proveedor' => $proveedor->nombre_proveedor,
                'monto' => (float) $g->monto,
                'nro_documento' => $g->nro_documento,
                'condicion_pago' => $g->condicion_pago,
            ]);

        $compras = DB::table('global.movimientos_inventario as m')
            ->leftJoin('global.inventario as i', 'm.id_inventario', '=', 'i.id_inventario')
            ->where('m.id_proveedor', $id)
            ->where('m.tipo_movimiento', 'COMPRA')
            ->select(
                'm.id_movimiento as id',
                'm.fecha_movimiento as fecha',
                'i.nombre_producto as concepto',
                DB::raw('COALESCE(m.costo_total, m.cantidad * m.costo_unitario, 0) as monto'),
                'm.condicion_pago',
                'm.documento_numero as nro_documento'
            )
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'tipo' => 'COMPRA',
                'fecha' => $c->fecha,
                'concepto' => $c->concepto,
                'proveedor' => $proveedor->nombre_proveedor,
                'monto' => (float) $c->monto,
                'nro_documento' => $c->nro_documento,
                'condicion_pago' => $c->condicion_pago,
            ]);

        $saldoInicial = (float) $this->config->numero('saldo_inicial_proveedores', 0.0);

        $movimientos = $gastos->concat($compras)->sortBy('fecha')->values();

        $saldo = $saldoInicial;
        $movimientos = $movimientos->map(function ($m) use (&$saldo) {
            // Una devolucion (monto negativo) reduce la deuda, no la aumenta.
            $saldo -= (float) $m['monto'];
            $m['saldo'] = round($saldo, 2);

            return $m;
        });

        $total = round($movimientos->sum('monto'), 2);

        return response()->json([
            'success' => true,
            'proveedor' => $proveedor,
            'movimientos' => $movimientos,
            'total_debitos' => $total,
            'saldo_actual' => round($saldoInicial - $total, 2),
        ]);
    }

    private function respuesta(Request $request, string $mensaje): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $mensaje]);
        }

        return redirect()->route('proveedores.index')->with('success', $mensaje);
    }
}