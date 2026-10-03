<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TipoGasto;
use App\Http\Requests\Gasto\StoreGastoGeneralRequest;
use App\Services\AuditoriaService;
use App\Services\ClasificadorGastoService;
use App\Services\DocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * REGISTRAR GASTOS GENERALES.
 *
 * Son los gastos de la EMPRESA que no se atribuyen a una unidad: servicios
 * basicos, impuestos, alquiler, telecomunicaciones, caja chica.
 *
 * CAMBIOS RESPECTO A LA VERSION ANTERIOR
 * --------------------------------------
 *  1. El `<select>` de categoria (con los literales 'Caja Chiva' y
 *     'Servicios B{sicos', mojibake incluido) se sustituye por el
 *     CLASIFICADOR. Solo se ofrecen los que tienen afecta_general = true.
 *  2. `categoria` pasa a ser el codigo canonico derivado del clasificador,
 *     no texto libre.
 *  3. El monto admite negativos (devolucion): la validacion anterior
 *     exigia min:0, de modo que la devolucion era imposible pese a que la
 *     vista ya Offeria el boton de confirmacion.
 *  4. Numeracion atomica serie GG_.
 */
class GastoGeneralController extends Controller
{
    public function __construct(
        private readonly DocumentoService $documentos,
        private readonly ClasificadorGastoService $clasificadores,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(): View
    {
        return view('gastos-generales.index');
    }

    public function create(): View
    {
        return view('gastos-generales.form', array_merge($this->datosDeFormulario(), ['gasto' => null]));
    }

    public function edit(string $id): View|RedirectResponse
    {
        $gasto = DB::table('global.gastos_generales')->where('id_gasto_general', $id)->first();

        if (!$gasto) {
            return redirect()->route('gastos-generales.index')->with('error', 'Gasto general no encontrado.');
        }

        return view('gastos-generales.form', array_merge($this->datosDeFormulario(), ['gasto' => $gasto]));
    }

    public function store(StoreGastoGeneralRequest $request): RedirectResponse
    {
        $datos = $request->datosNormalizados($this->clasificadores);
        $datos['nro_documento'] = $this->documentos->siguiente('GG');
        $datos['estado'] = 'ACTIVO';

        $id = DB::table('global.gastos_generales')->insertGetId($datos, 'id_gasto_general');

        $this->auditoria->registrar(
            $datos['es_devolucion'] ? 'DEVOLUCION_GENERAL_REGISTRADA' : 'GASTO_GENERAL_REGISTRADO',
            'gastos_generales',
            sprintf('%s de Bs. %s', $datos['nro_documento'], number_format((float) $datos['monto'], 2)),
            ['id' => $id, 'categoria' => $datos['categoria'], 'devolucion' => $datos['es_devolucion']]
        );

        $mensaje = $datos['es_devolucion']
            ? "Devolucion {$datos['nro_documento']} registrada exitosamente"
            : "Gasto general {$datos['nro_documento']} registrado exitosamente";

        return redirect()->route('gastos-generales.index')->with('success', $mensaje);
    }

    public function update(StoreGastoGeneralRequest $request, string $id): RedirectResponse
    {
        $existente = DB::table('global.gastos_generales')->where('id_gasto_general', $id)->first();

        if (!$existente) {
            return redirect()->route('gastos-generales.index')->with('error', 'Gasto general no encontrado.');
        }

        $datos = $request->datosNormalizados($this->clasificadores);
        $datos['updated_at'] = now();

        // El numero de documento y el estado son inmutables desde el formulario.
        unset($datos['nro_documento'], $datos['estado']);

        DB::table('global.gastos_generales')->where('id_gasto_general', $id)->update($datos);

        $this->auditoria->registrar('GASTO_GENERAL_ACTUALIZADO', 'gastos_generales', "Gasto general #{$id} actualizado", ['id' => $id]);

        return redirect()->route('gastos-generales.index')->with('success', 'Gasto general actualizado exitosamente');
    }

    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        if (!$request->user()?->can('eliminar', 'gastos_generales')) {
            abort(403, 'Su rol no permite eliminar gastos generales.');
        }

        $gasto = DB::table('global.gastos_generales')->where('id_gasto_general', $id)->first();

        if (!$gasto) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Gasto no encontrado.'], 404);
            }

            return redirect()->route('gastos-generales.index')->with('error', 'Gasto no encontrado.');
        }

        // Anulacion logica: el gasto general queda registrado pero fuera de
        // los totales. Borrarlo fisico perderia el rastro contable.
        DB::table('global.gastos_generales')
            ->where('id_gasto_general', $id)
            ->update(['estado' => 'ANULADO', 'updated_at' => now()]);

        $this->auditoria->registrar('GASTO_GENERAL_ANULADO', 'gastos_generales', "Gasto {$gasto->nro_documento} anulado", ['id' => $id]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Gasto general anulado']);
        }

        return redirect()->route('gastos-generales.index')->with('success', 'Gasto general anulado');
    }

    /**
     * Listado unificado: gastos de `gastos_generales` + los gastos legacy de
     * `gastos` que no tienen unidad asignada.
     */
    public function apiList(Request $request): JsonResponse
    {
        $nuevos = DB::table('global.gastos_generales as gg')
            ->leftJoin('global.bancos as b', 'gg.id_banco', '=', 'b.id_banco')
            ->leftJoin('global.proveedores as p', 'gg.id_proveedor', '=', 'p.id_proveedor')
            ->leftJoin('global.clasificador_gastos as c', 'gg.id_clasificador', '=', 'c.id_clasificador')
            ->select(
                'gg.id_gasto_general as id',
                'gg.nro_documento',
                'gg.categoria',
                'gg.concepto',
                'gg.monto',
                'gg.fecha_gasto',
                'gg.condicion_pago',
                'gg.nro_comprobante',
                'gg.observaciones',
                'gg.estado',
                'gg.es_devolucion',
                'gg.id_clasificador',
                'b.nombre_banco',
                'p.nombre_proveedor',
                'c.codigo as clasificador_codigo',
                'c.descripcion as clasificador_descripcion',
                DB::raw("'CLASIFICADOR' as origen")
            )
            ->where('gg.estado', '<>', 'ANULADO');

        $this->aplicarFiltros($nuevos, $request);

        // Legacy: gastos sin unidad (servicios de la empresa cargados antes
        // de que existiera gastos_generales). Se deprecara con el tiempo.
        $legacy = DB::table('global.gastos as g')
            ->leftJoin('global.proveedores as p', 'g.id_proveedor', '=', 'p.id_proveedor')
            ->whereNull('g.id_vehiculo')
            ->select(
                'g.id_gasto as id',
                'g.nro_documento',
                'g.tipo_gasto as categoria',
                'g.concepto',
                'g.monto',
                'g.fecha_gasto',
                DB::raw("'CONTADO' as condicion_pago"),
                'g.nro_documento as nro_comprobante',
                'g.descripcion as observaciones',
                DB::raw("'ACTIVO' as estado"),
                'g.es_devolucion',
                'g.id_clasificador',
                DB::raw("NULL as nombre_banco"),
                'p.nombre_proveedor',
                DB::raw("NULL as clasificador_codigo"),
                DB::raw("NULL as clasificador_descripcion"),
                DB::raw("'LEGACY' as origen")
            );

        if ($request->filled('fecha_inicio')) {
            $legacy->where('g.fecha_gasto', '>=', $request->date('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $legacy->where('g.fecha_gasto', '<=', $request->date('fecha_fin'));
        }

        $data = $nuevos->get()->concat($legacy->get())->sortByDesc('fecha_gasto')->values();

        $total = $data->sum(fn ($g) => (float) $g->monto);
        $devoluciones = $data->where('es_devolucion', true)->sum(fn ($g) => (float) $g->monto);

        return response()->json([
            'success' => true,
            'data' => $data,
            'resumen' => [
                'total' => round($total, 2),
                'total_devoluciones' => round($devoluciones, 2),
                'cantidad' => $data->count(),
            ],
        ]);
    }

    public function apiShow(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.gastos_generales')->where('id_gasto_general', $id)->first(),
        ]);
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    private function aplicarFiltros($query, Request $request): void
    {
        if ($request->filled('categoria')) {
            $query->where('gg.categoria', $request->string('categoria')->value());
        }

        if ($request->filled('id_clasificador')) {
            $query->where('gg.id_clasificador', $request->integer('id_clasificador'));
        }

        if ($request->filled('es_devolucion')) {
            $query->where('gg.es_devolucion', $request->boolean('es_devolucion'));
        }

        if ($request->filled('fecha_inicio')) {
            $query->where('gg.fecha_gasto', '>=', $request->date('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->where('gg.fecha_gasto', '<=', $request->date('fecha_fin'));
        }
    }

    /** @return array<string, mixed> */
    private function datosDeFormulario(): array
    {
        return [
            'bancos' => DB::table('global.bancos')->where('estado', 'ACTIVO')->orderBy('nombre_banco')->get(),
            'proveedores' => DB::table('global.proveedores')->where('estado', 1)->orderBy('nombre_proveedor')->get(),
            // SOLO los clasificadores de alcance general.
            'clasificadores' => $this->clasificadores->listar('general'),
            'tipos' => TipoGasto::values(),
        ];
    }
}