<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * GRUPOS DE GASTOS: el clasificador de gastos se organiza aqui.
 *
 * Cada grupo lleva el TIPO canonico del gasto (`tipo_gasto`) y es a el a
 * quien referencian los gastos (`gastos.id_grupo_gasto`,
 * `gastos_generales.id_grupo_gasto`). La tabla clasificador_gastos queda
 * solo como legado historico.
 */
class GrupoGastoController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    public function index(): View
    {
        $grupos = DB::table('global.grupos_gastos')
            ->leftJoin(
                'global.clasificador_gastos',
                'clasificador_gastos.id_grupo_gasto',
                '=',
                'grupos_gastos.id_grupo_gasto',
            )
            ->groupBy(
                'grupos_gastos.id_grupo_gasto',
                'grupos_gastos.nombre',
                'grupos_gastos.tipo_gasto',
                'grupos_gastos.descripcion',
                'grupos_gastos.created_at',
            )
            ->orderBy('grupos_gastos.nombre')
            ->selectRaw(
                'grupos_gastos.id_grupo_gasto, grupos_gastos.nombre, grupos_gastos.tipo_gasto, grupos_gastos.descripcion, ' .
                'COUNT(clasificador_gastos.id_clasificador) AS clasificadores'
            )
            ->get();

        return view('grupos-gastos.index', ['grupos' => $grupos]);
    }

    public function create(): View
    {
        return view('grupos-gastos.form', [
            'grupo' => null,
            'tiposGasto' => \App\Enums\TipoGasto::values(),
        ]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $grupo = DB::table('global.grupos_gastos')->where('id_grupo_gasto', $id)->first();

        if (!$grupo) {
            return redirect()->route('grupos-gastos.index')->with('error', 'Grupo de gastos no encontrado.');
        }

        return view('grupos-gastos.form', [
            'grupo' => $grupo,
            'tiposGasto' => \App\Enums\TipoGasto::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'tipo_gasto' => ['nullable', 'string', 'in:' . implode(',', \App\Enums\TipoGasto::values())],
        ], [
            'nombre.required' => 'El nombre del grupo es obligatorio.',
        ]);

        $existe = DB::table('global.grupos_gastos')
            ->whereRaw('LOWER(nombre) = LOWER(?)', [$datos['nombre']])
            ->exists();

        if ($existe) {
            return back()->withErrors(['nombre' => 'Ya existe un grupo de gastos con ese nombre.'])->withInput();
        }

        $id = DB::table('global.grupos_gastos')->insertGetId([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'tipo_gasto' => $datos['tipo_gasto'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_grupo_gasto');

        $this->auditoria->registrar(
            'GRUPO_GASTO_CREADO',
            'clasificadores',
            "Grupo de gastos {$datos['nombre']} creado",
            ['id' => $id],
        );

        return redirect()->route('grupos-gastos.index')->with('success', 'Grupo de gastos registrado exitosamente');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $grupo = DB::table('global.grupos_gastos')->where('id_grupo_gasto', $id)->first();

        if (!$grupo) {
            return redirect()->route('grupos-gastos.index')->with('error', 'Grupo de gastos no encontrado.');
        }

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'tipo_gasto' => ['nullable', 'string', 'in:' . implode(',', \App\Enums\TipoGasto::values())],
        ], [
            'nombre.required' => 'El nombre del grupo es obligatorio.',
        ]);

        $existe = DB::table('global.grupos_gastos')
            ->whereRaw('LOWER(nombre) = LOWER(?)', [$datos['nombre']])
            ->where('id_grupo_gasto', '!=', $grupo->id_grupo_gasto)
            ->exists();

        if ($existe) {
            return back()->withErrors(['nombre' => 'Ya existe otro grupo de gastos con ese nombre.'])->withInput();
        }

        DB::table('global.grupos_gastos')
            ->where('id_grupo_gasto', $grupo->id_grupo_gasto)
            ->update([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'tipo_gasto' => $datos['tipo_gasto'] ?? null,
                'updated_at' => now(),
            ]);

        $this->auditoria->registrar(
            'GRUPO_GASTO_ACTUALIZADO',
            'clasificadores',
            "Grupo de gastos {$datos['nombre']} actualizado",
            ['id' => $grupo->id_grupo_gasto],
        );

        return redirect()->route('grupos-gastos.index')->with('success', 'Grupo de gastos actualizado exitosamente');
    }

    /**
      Catalogo para formularios y filtros (select de GRUPO GASTO).
      */
    public function apiList(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.grupos_gastos')->orderBy('nombre')->get(),
        ]);
    }

    /**
      Solo se borra fisicamente si ningun gasto lo referencia: los grupos
      ya son la clasificacion real, asi que borrar uno usado moveria de
      golpe gastos existentes.
      */
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $grupo = DB::table('global.grupos_gastos')->where('id_grupo_gasto', $id)->first();

        if (!$grupo) {
            return $this->respuesta($request, false, 'Grupo de gastos no encontrado.');
        }

        $enUso = DB::table('global.gastos')->where('id_grupo_gasto', $grupo->id_grupo_gasto)->exists()
            || DB::table('global.gastos_generales')->where('id_grupo_gasto', $grupo->id_grupo_gasto)->exists();

        if ($enUso) {
            return $this->respuesta(
                $request,
                false,
                'No se puede eliminar: el grupo tiene gastos registrados.'
            );
        }

        DB::table('global.grupos_gastos')->where('id_grupo_gasto', $grupo->id_grupo_gasto)->delete();

        $this->auditoria->registrar(
            'GRUPO_GASTO_ELIMINADO',
            'clasificadores',
            "Grupo de gastos {$grupo->nombre} eliminado",
            ['id' => $grupo->id_grupo_gasto],
        );

        return $this->respuesta($request, true, 'Grupo de gastos eliminado');
    }

    private function respuesta(Request $request, bool $ok, string $mensaje): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $ok, 'message' => $mensaje], $ok ? 200 : 422);
        }

        return $ok
            ? redirect()->route('grupos-gastos.index')->with('success', $mensaje)
            : redirect()->route('grupos-gastos.index')->with('error', $mensaje);
    }
}
