<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreGrupoRequest;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** GRUPOS / categorias del almacen. */
class GrupoController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    public function index(): View
    {
        return view('grupos.index');
    }

    public function create(): View
    {
        return view('grupos.form', ['grupo' => null]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $grupo = DB::table('global.categorias_almacen')->where('id_categoria', $id)->first();

        if (!$grupo) {
            return redirect()->route('grupos.index')->with('error', 'Grupo no encontrado.');
        }

        return view('grupos.form', ['grupo' => $grupo]);
    }

    public function store(StoreGrupoRequest $request): RedirectResponse
    {
        $id = DB::table('global.categorias_almacen')->insertGetId($request->datosNormalizados(), 'id_categoria');

        $this->auditoria->registrar('GRUPO_CREADO', 'grupos', "Grupo #{$id} creado", ['id' => $id]);

        return redirect()->route('grupos.index')->with('success', 'Grupo registrado exitosamente');
    }

    public function update(StoreGrupoRequest $request, string $id): RedirectResponse
    {
        DB::table('global.categorias_almacen')
            ->where('id_categoria', $id)
            ->update($request->datosActualizables());

        $this->auditoria->registrar('GRUPO_ACTUALIZADO', 'grupos', "Grupo #{$id} actualizado", ['id' => $id]);

        return redirect()->route('grupos.index')->with('success', 'Grupo actualizado exitosamente');
    }

    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        if (!$request->user()?->can('eliminar', 'grupos')) {
            abort(403, 'Su rol no permite eliminar grupos.');
        }

        $tieneProductos = DB::table('global.inventario')->where('id_categoria', $id)->exists();

        if ($tieneProductos) {
            return $this->respuesta(
                $request,
                false,
                'No se puede eliminar: el grupo tiene productos asociados.'
            );
        }

        DB::table('global.categorias_almacen')->where('id_categoria', $id)->delete();

        $this->auditoria->registrar('GRUPO_ELIMINADO', 'grupos', "Grupo #{$id} eliminado", ['id' => $id]);

        return $this->respuesta($request, true, 'Grupo eliminado');
    }

    public function apiList(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.categorias_almacen')
                ->select(
                    'global.categorias_almacen.*',
                    DB::raw('(SELECT COUNT(*) FROM global.inventario
                              WHERE global.inventario.id_categoria = global.categorias_almacen.id_categoria
                                AND global.inventario.estado = \'ACTIVO\') as total_productos')
                )
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function apiShow(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.categorias_almacen')->where('id_categoria', $id)->first(),
        ]);
    }

    private function respuesta(Request $request, bool $ok, string $mensaje): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $ok, 'message' => $mensaje], $ok ? 200 : 422);
        }

        return $ok
            ? redirect()->route('grupos.index')->with('success', $mensaje)
            : redirect()->route('grupos.index')->with('error', $mensaje);
    }
}