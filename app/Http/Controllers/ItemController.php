<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Services\AuditoriaService;
use App\Services\InventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ITEMS: alta y edicion de productos del inventario.
 *
 * El `stock_actual` NO se edita desde el formulario (ver
 * StoreItemRequest::camposActualizables): es consecuencia de los
 * movimientos. Editarlo a mano desincronizaba el inventario del kardex.
 */
class ItemController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventario,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(): View
    {
        return view('items.index');
    }

    public function create(): View
    {
        return view('items.form', [
            'item' => null,
            'categorias' => $this->categorias(),
        ]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $item = DB::table('global.inventario')->where('id_inventario', $id)->first();

        if (!$item) {
            return redirect()->route('items.index')->with('error', 'Item no encontrado.');
        }

        return view('items.form', [
            'item' => $item,
            'categorias' => $this->categorias(),
        ]);
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        $datos = $request->datosNormalizados();

        $datos['estado'] ??= 'ACTIVO';
        $datos['stock_actual'] = $request->input('stock_actual') ?? 0;
        $datos['created_by'] = auth()->user()?->getKey();

        if (empty($datos['codigo'])) {
            $datos['codigo'] = $this->inventario->generarCodigoProducto(
                $this->nombreGrupo($datos['id_categoria'] ?? null),
                isset($datos['id_categoria']) ? (int) $datos['id_categoria'] : null,
            );
        }

        $id = DB::table('global.inventario')->insertGetId($datos, 'id_inventario');

        $this->auditoria->registrar('ITEM_CREADO', 'items', "Item {$datos['codigo']} creado", ['id' => $id]);

        return redirect()->route('items.index')->with('success', 'Item registrado exitosamente');
    }

    public function update(StoreItemRequest $request, string $id): RedirectResponse
    {
        $datos = $request->datosActualizables();
        $datos['updated_at'] = now();

        DB::table('global.inventario')->where('id_inventario', $id)->update($datos);

        $this->auditoria->registrar('ITEM_ACTUALIZADO', 'items', "Item #{$id} actualizado", ['id' => $id]);

        return redirect()->route('items.index')->with('success', 'Item actualizado exitosamente');
    }

    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        DB::table('global.inventario')
            ->where('id_inventario', $id)
            ->update(['estado' => 'INACTIVO', 'updated_at' => now()]);

        $this->auditoria->registrar('ITEM_DESACTIVADO', 'items', "Item #{$id} desactivado", ['id' => $id]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Item desactivado']);
        }

        return redirect()->route('items.index')->with('success', 'Item desactivado');
    }

    public function apiList(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.inventario')
                ->leftJoin('global.categorias_almacen', 'global.inventario.id_categoria', '=', 'global.categorias_almacen.id_categoria')
                ->select('global.inventario.*', 'global.categorias_almacen.nombre as categoria')
                ->where('global.inventario.estado', 'ACTIVO')
                ->orderBy('global.inventario.nombre_producto')
                ->get(),
        ]);
    }

    public function apiShow(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.inventario')
                ->leftJoin('global.categorias_almacen', 'global.inventario.id_categoria', '=', 'global.categorias_almacen.id_categoria')
                ->select('global.inventario.*', 'global.categorias_almacen.nombre as categoria')
                ->where('global.inventario.id_inventario', $id)
                ->first(),
        ]);
    }

    private function categorias()
    {
        return DB::table('global.categorias_almacen')->orderBy('nombre')->get();
    }

    private function nombreGrupo(?int $idCategoria): ?string
    {
        if (!$idCategoria) {
            return null;
        }

        return DB::table('global.categorias_almacen')
            ->where('id_categoria', $idCategoria)
            ->value('nombre');
    }
}