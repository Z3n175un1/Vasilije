<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBancoRequest;
use App\Services\AuditoriaService;
use App\Services\DocumentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CUENTAS BANCARIAS y su estado de cuenta.
 *
 * El saldo de una cuenta no se almacena de forma fiable: se DERIVA del
 * saldo inicial mas los movimientos CONTADO (gastos y compras pagadas).
 * `saldo_actual` se mantiene actualizado en la columna, pero la fuente de
 * verdad es el estado de cuenta.
 */
class BancoController extends Controller
{
    public function __construct(
        private readonly DocumentoService $documentos,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(): View
    {
        return view('bancos.index');
    }

    public function create(): View
    {
        return view('bancos.form', ['banco' => null]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $banco = DB::table('global.bancos')->where('id_banco', $id)->first();

        if (!$banco) {
            return redirect()->route('bancos.index')->with('error', 'Banco no encontrado.');
        }

        return view('bancos.form', ['banco' => $banco]);
    }

    public function store(StoreBancoRequest $request): RedirectResponse
    {
        $id = DB::table('global.bancos')->insertGetId($request->datosNormalizados(), 'id_banco');

        $this->auditoria->registrar('BANCO_CREADO', 'bancos', "Cuenta bancaria #{$id} creada", ['id' => $id]);

        return redirect()->route('bancos.index')->with('success', 'Banco registrado exitosamente');
    }

    public function update(StoreBancoRequest $request, string $id): RedirectResponse
    {
        DB::table('global.bancos')
            ->where('id_banco', $id)
            ->update($request->datosNormalizados() + ['updated_at' => now()]);

        $this->auditoria->registrar('BANCO_ACTUALIZADO', 'bancos', "Cuenta bancaria #{$id} actualizada", ['id' => $id]);

        return redirect()->route('bancos.index')->with('success', 'Banco actualizado exitosamente');
    }

    /** Baja logica: una cuenta con movimientos no se borra. */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        DB::table('global.bancos')
            ->where('id_banco', $id)
            ->update(['estado' => 'INACTIVO', 'updated_at' => now()]);

        $this->auditoria->registrar('BANCO_DESACTIVADO', 'bancos', "Cuenta bancaria #{$id} desactivada", ['id' => $id]);

        return redirect()->route('bancos.index')->with('success', 'Banco desactivado');
    }

    /**
     * Estado de cuenta: movimientos CONTADOS que salen de la cuenta.
     *
     * Solo se consideran los pagos en efectivo/contado: los creditos son
     * deuda con el proveedor, no salida de caja.
     */
    public function estado(string $id): View|RedirectResponse
    {
        $banco = DB::table('global.bancos')->where('id_banco', $id)->first();

        if (!$banco) {
            return redirect()->route('bancos.index')->with('error', 'Banco no encontrado.');
        }

        $gastos = DB::table('global.gastos')
            ->leftJoin('global.vehiculos', 'global.gastos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->leftJoin('global.proveedores', 'global.gastos.id_proveedor', '=', 'global.proveedores.id_proveedor')
            ->where('global.gastos.id_banco', $id)
            ->where('global.gastos.condicion_pago', 'CONTADO')
            ->select(
                'global.gastos.id_gasto as id',
                'global.gastos.fecha_gasto as fecha',
                'global.gastos.concepto',
                'global.gastos.monto',
                'global.gastos.condicion_pago',
                'global.gastos.nro_documento',
                'global.gastos.es_devolucion',
                'global.vehiculos.placa_vehiculo',
                DB::raw("COALESCE(global.proveedores.nombre_proveedor, '') as proveedor")
            )
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'tipo' => 'GASTO',
                'fecha' => $g->fecha,
                'concepto' => $g->concepto,
                'proveedor' => $g->proveedor,
                'vehiculo' => $g->placa_vehiculo,
                'monto' => (float) $g->monto,
                'nro_documento' => $g->nro_documento,
                'condicion_pago' => $g->condicion_pago,
                'es_devolucion' => (bool) $g->es_devolucion,
            ]);

        $compras = DB::table('global.movimientos_inventario')
            ->leftJoin('global.inventario', 'global.movimientos_inventario.id_inventario', '=', 'global.inventario.id_inventario')
            ->leftJoin('global.proveedores', 'global.movimientos_inventario.id_proveedor', '=', 'global.proveedores.id_proveedor')
            ->where('global.movimientos_inventario.id_banco', $id)
            ->where('global.movimientos_inventario.tipo_movimiento', 'COMPRA')
            ->where('global.movimientos_inventario.condicion_pago', 'CONTADO')
            ->select(
                'global.movimientos_inventario.id_movimiento as id',
                'global.movimientos_inventario.fecha_movimiento as fecha',
                'global.inventario.nombre_producto as concepto',
                DB::raw('COALESCE(global.movimientos_inventario.costo_total, global.movimientos_inventario.cantidad * global.movimientos_inventario.costo_unitario, 0) as monto'),
                'global.movimientos_inventario.condicion_pago',
                DB::raw("COALESCE(global.movimientos_inventario.documento_numero, '') as nro_documento"),
                DB::raw("COALESCE(global.proveedores.nombre_proveedor, '') as proveedor")
            )
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'tipo' => 'COMPRA',
                'fecha' => $c->fecha,
                'concepto' => $c->concepto,
                'proveedor' => $c->proveedor,
                'vehiculo' => null,
                'monto' => (float) $c->monto,
                'nro_documento' => $c->nro_documento,
                'condicion_pago' => $c->condicion_pago,
                'es_devolucion' => false,
            ]);

        $movimientos = $gastos->concat($compras)->sortBy('fecha')->values();

        $saldo = (float) $banco->saldo_inicial;

        $movimientos = $movimientos->map(function ($m) use (&$saldo) {
            // Un monto negativo es una devolucion: ENTRADA de dinero, no salida.
            $saldo -= (float) $m['monto'];
            $m['saldo'] = round($saldo, 2);

            return $m;
        });

        $totalDebitos = round($movimientos->where('monto', '>', 0)->sum('monto'), 2);
        $totalDevoluciones = round(abs($movimientos->where('monto', '<', 0)->sum('monto')), 2);

        return view('bancos.estado', compact(
            'banco',
            'movimientos',
            'totalDebitos',
            'totalDevoluciones'
        ) + ['saldoActual' => round((float) $banco->saldo_inicial - $movimientos->sum('monto'), 2)]);
    }

    public function apiList(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.bancos')->orderBy('nombre_banco')->get(),
        ]);
    }

    public function apiShow(string $id): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.bancos')->where('id_banco', $id)->first(),
        ]);
    }
}