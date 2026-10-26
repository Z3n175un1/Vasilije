<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TipoGasto;
use App\Http\Requests\Clasificador\StoreClasificadorRequest;
use App\Services\AuditoriaService;
use App\Services\ClasificadorGastoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CLASIFICADOR DE GASTOS (catalogo maestro).
 *
 * Este modulo sustituye a los <select> con valores literales que cada
 * vista repetia y que entre si no coincidian. Cada clasificador declara:
 *
 *   - Codigo ID        (autogenerado CG-0001)
 *   - Descripcion      (texto libre)
 *   - Tipo de Gasto    (catalogo canonico App\Enums\TipoGasto)
 *   - Afecta a Unidad  (booleano)
 *   - Afecta en forma General (booleano)
 *
 * El alcance es lo que gobierna la visibilidad: al registrar un gasto
 * desde una UNIDAD solo se ofrecen los clasificadores con
 * `afecta_unidad = true`; al registrar un gasto general, solo los de
 * `afecta_general = true`. El backend lo revalida: el filtro del <select>
 * no es la unica barrera.
 */
class ClasificadorController extends Controller
{
    public function __construct(
        private readonly ClasificadorGastoService $clasificadores,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(): View
    {
        $clasificadores = $this->clasificadores->listar('todos', false);

        $resumen = [
            'total' => $clasificadores->count(),
            'unidad' => $clasificadores->where('afecta_unidad', true)->count(),
            'general' => $clasificadores->where('afecta_general', true)->count(),
            'ambos' => $clasificadores->filter(
                static fn ($c) => $c->afecta_unidad && $c->afecta_general
            )->count(),
            'inactivos' => $clasificadores->where('estado', 'INACTIVO')->count(),
        ];

        // Agrupacion por grupo de gastos (HU grupos). La consulta ya llega
        // ordenada por descripcion; los bloques se ordenan por nombre de
        // grupo y SIN GRUPO queda al final.
        $porGrupo = $clasificadores->groupBy(static fn ($c) => $c->grupo_gasto ?? 'SIN GRUPO');
        $ordenados = [];

        foreach ($porGrupo->sortBy(static fn ($i, $clave) => $clave, SORT_NATURAL | SORT_FLAG_CASE) as $clave => $items) {
            $ordenados[$clave] = $items;
        }

        if (isset($ordenados['SIN GRUPO'])) {
            $sinGrupo = $ordenados['SIN GRUPO'];
            unset($ordenados['SIN GRUPO']);
            $ordenados['SIN GRUPO'] = $sinGrupo;
        }

        return view('clasificadores.index', [
            'clasificadores' => $clasificadores,
            'clasificadoresAgrupados' => $ordenados,
            'tipos' => TipoGasto::cases(),
            'resumen' => $resumen,
        ]);
    }

    public function create(): View
    {
        return view('clasificadores.form', [
            'clasificador' => null,
            'tipos' => TipoGasto::cases(),
            'grupos' => $this->clasificadores->grupos(),

            // El código no se escribe: se muestra el que le tocará para que el
            // operador sepa de antemano qué se va a registrar.
            'codigoSugerido' => $this->clasificadores->siguienteCodigo(),
        ]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $clasificador = $this->clasificadores->buscar((int) $id);

        if (!$clasificador) {
            return redirect()->route('clasificadores.index')->with('error', 'Clasificador no encontrado.');
        }

        return view('clasificadores.form', [
            'clasificador' => $clasificador,
            'tipos' => TipoGasto::cases(),
            'grupos' => $this->clasificadores->grupos(),
        ]);
    }

    public function store(StoreClasificadorRequest $request): RedirectResponse
    {
        $id = $this->clasificadores->crear($request->datosNormalizados());

        $this->auditoria->registrar('CLASIFICADOR_CREADO', 'clasificadores', "Clasificador creado: #{$id}", [
            'id' => $id,
            'tipo' => $request->input('tipo_gasto'),
            'alcance' => $this->alcance($request),
        ]);

        return redirect()->route('clasificadores.index')
            ->with('success', 'Clasificador de gasto registrado exitosamente');
    }

    public function update(StoreClasificadorRequest $request, string $id): RedirectResponse
    {
        if (!$this->clasificadores->buscar((int) $id)) {
            return redirect()->route('clasificadores.index')->with('error', 'Clasificador no encontrado.');
        }

        $this->clasificadores->actualizar((int) $id, $request->datosNormalizados());

        $this->auditoria->registrar('CLASIFICADOR_ACTUALIZADO', 'clasificadores', "Clasificador #{$id} actualizado", [
            'id' => $id,
            'alcance' => $this->alcance($request),
        ]);

        return redirect()->route('clasificadores.index')
            ->with('success', 'Clasificador de gasto actualizado exitosamente');
    }

    /**
     * Desactiva el clasificador. Solo se borra fisicamente si ningun gasto lo
     * referencia: el historico contable no admite borrados.
     */
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $clasificador = $this->clasificadores->buscar((int) $id);

        if (!$clasificador) {
            return $this->respuesta($request, false, 'Clasificador no encontrado.', 404);
        }

        $eliminado = $this->clasificadores->desactivar((int) $id);

        $this->auditoria->registrar(
            $eliminado ? 'CLASIFICADOR_ELIMINADO' : 'CLASIFICADOR_DESACTIVADO',
            'clasificadores',
            "Clasificador {$clasificador->codigo} " . ($eliminado ? 'eliminado' : 'desactivado'),
            ['id' => $id, 'tiene_gastos' => !$eliminado]
        );

        return $this->respuesta(
            $request,
            true,
            $eliminado
                ? 'Clasificador eliminado'
                : 'Clasificador desactivado (tiene gastos asociados, se conserva el historico)'
        );
    }

    /**
     * Consumido por los formularios de gasto via fetch.
     *
     * ?alcance=unidad   -> solo afecta_unidad
     * ?alcance=general  -> solo afecta_general
     */
    public function apiList(Request $request): JsonResponse
    {
        $alcance = $request->query('alcance', 'todos');

        if (!in_array($alcance, ['unidad', 'general', 'todos'], true)) {
            $alcance = 'todos';
        }

        $clasificadores = $this->clasificadores->listar($alcance);

        return response()->json([
            'success' => true,
            'alcance' => $alcance,
            'data' => $clasificadores->map(static fn ($c) => [
                'id_clasificador' => $c->id_clasificador,
                'codigo' => $c->codigo,
                'descripcion' => $c->descripcion,
                'tipo_gasto' => $c->tipo_gasto,
                'tipo_label' => TipoGasto::tryFrom($c->tipo_gasto)?->label() ?? $c->tipo_gasto,
                'grupo_gasto' => $c->grupo_gasto,
                'afecta_unidad' => (bool) $c->afecta_unidad,
                'afecta_general' => (bool) $c->afecta_general,
            ])->values(),
        ]);
    }

    public function apiShow(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->clasificadores->buscar((int) $id),
        ]);
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    /** @return array<string, bool> */
    private function alcance(StoreClasificadorRequest $request): array
    {
        return [
            'unidad' => $request->boolean('afecta_unidad'),
            'general' => $request->boolean('afecta_general'),
        ];
    }

    private function respuesta(Request $request, bool $ok, string $mensaje, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => $ok, 'message' => $mensaje], $status);
        }

        return $ok
            ? redirect()->route('clasificadores.index')->with('success', $mensaje)
            : redirect()->route('clasificadores.index')->with('error', $mensaje);
    }
}