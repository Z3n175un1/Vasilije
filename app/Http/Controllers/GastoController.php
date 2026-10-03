<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoVehiculo;
use App\Enums\Rol;
use App\Enums\TipoGasto;
use App\Http\Requests\Gasto\StoreGastoUnidadRequest;
use App\Services\AuditoriaService;
use App\Services\ClasificadorGastoService;
use App\Services\DocumentoService;
use App\Services\GastoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Gastos OPERACIONALES de la flota (uno por unidad, fecha y concepto).
 *
 * CAMBIOS RESPECTO A LA VERSION ANTERIOR
 * --------------------------------------
 *  1. El tipo de gasto se elige de un CLASIFICADOR, no de un <select> con
 *     literales. Solo aparecen los clasificadores con afecta_unidad = true.
 *  2. El tipo se DERIVA del clasificador, lo que elimina de raiz la
 *     contradiccion entre el enum de PHP ('Sueldo') y el CHECK de la BD
 *     ('Sueldos').
 *  3. El monto admite negativos: es una DEVOLUCION. Antes el formulario
 *     permitia escribirlo pero la validacion del general lo rechazaba con
 *     min:0, y el estado de pago quedaba incoherente.
 *  4. La numeracion usa la secuencia atomica de la serie E_.
 *  5. Se respeta el rol: un usuario de solo lectura no registra gastos.
 */
class GastoController extends Controller
{
    public function __construct(
        private readonly DocumentoService $documentos,
        private readonly GastoService $gastos,
        private readonly ClasificadorGastoService $clasificadores,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('mantenimiento.index');
    }

    public function create(Request $request): View
    {
        $datos = $this->datosDeFormulario();

        return view('gastos.form', array_merge($datos, [
            'gasto' => null,
            'id_vehiculo' => $request->query('id_vehiculo'),
        ]));
    }

    public function edit(string $id): View|RedirectResponse
    {
        $gasto = DB::table('global.gastos')->where('id_gasto', $id)->first();

        if (!$gasto) {
            return redirect()->route('dashboard.index')->with('error', 'Gasto no encontrado.');
        }

        if ($gasto->tipo_gasto === TipoGasto::Combustible->value) {
            $gasto->combustible = DB::table('global.combustible_detalle')->where('id_gasto', $id)->first();
        }

        return view('gastos.form', array_merge($this->datosDeFormulario(), [
            'gasto' => $gasto,
            'id_vehiculo' => null,
        ]));
    }

    public function store(StoreGastoUnidadRequest $request): JsonResponse|RedirectResponse
    {
        $datos = $request->datosNormalizados($this->clasificadores);

        $idGasto = DB::transaction(function () use ($datos, $request): int {
            $datos['nro_documento'] = $this->documentos->siguiente('E');
            $datos['creado_por'] = $request->user()?->id;
            $datos['fecha_registro'] = now();
            $datos['fecha_actualizacion'] = now();

            $id = DB::table('global.gastos')->insertGetId($datos, 'id_gasto');

            [$tipo, $litros, $precio] = $request->detalleCombustible();
            if ($datos['tipo_gasto'] === TipoGasto::Combustible->value) {
                $this->gastos->sincronizarCombustible($id, $tipo, $litros, $precio);
            }

            return $id;
        });

        $gasto = DB::table('global.gastos')->where('id_gasto', $idGasto)->first();

        $this->auditoria->registrar(
            $datos['es_devolucion'] ? 'DEVOLUCION_REGISTRADA' : 'GASTO_REGISTRADO',
            'gastos',
            sprintf('Gasto %s de Bs. %s', $gasto->nro_documento, number_format((float) $datos['monto'], 2)),
            ['id_gasto' => $idGasto, 'tipo' => $datos['tipo_gasto'], 'devolucion' => $datos['es_devolucion']]
        );

        return $this->respuesta(
            $request,
            $gasto,
            $datos['es_devolucion']
                ? sprintf('Devolucion %s registrada exitosamente', $gasto->nro_documento)
                : sprintf('Gasto %s registrado exitosamente', $gasto->nro_documento)
        );
    }

    public function update(StoreGastoUnidadRequest $request, string $id): JsonResponse|RedirectResponse
    {
        $existente = DB::table('global.gastos')->where('id_gasto', $id)->first();

        if (!$existente) {
            return $this->error($request, 'Gasto no encontrado.', 404);
        }

        $datos = $request->datosNormalizados($this->clasificadores);

        // El numero de documento es inmutable: los reportes y el estado de
        // cuenta lo referencian.
        unset($datos['nro_documento'], $datos['creado_por']);

        $datos['fecha_actualizacion'] = now();

        DB::transaction(function () use ($id, $datos, $request): void {
            DB::table('global.gastos')->where('id_gasto', $id)->update($datos);

            if ($datos['tipo_gasto'] === TipoGasto::Combustible->value) {
                [$tipo, $litros, $precio] = $request->detalleCombustible();
                $this->gastos->sincronizarCombustible((int) $id, $tipo, $litros, $precio);
            } else {
                DB::table('global.combustible_detalle')->where('id_gasto', $id)->delete();
            }
        });

        $this->auditoria->registrar('GASTO_ACTUALIZADO', 'gastos', "Gasto #{$id} actualizado", ['id_gasto' => $id]);

        return $this->respuesta($request, $existente, 'Gasto actualizado exitosamente');
    }

    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        if (!$request->user()?->can('eliminar', 'gastos')) {
            abort(403, 'Su rol no permite eliminar gastos.');
        }

        $gasto = DB::table('global.gastos')->where('id_gasto', $id)->first();

        if (!$gasto) {
            return $this->error($request, 'Gasto no encontrado.', 404);
        }

        $this->gastos->eliminarGastoUnidad((int) $id);

        $this->auditoria->registrar('GASTO_ELIMINADO', 'gastos', "Gasto {$gasto->nro_documento} eliminado", [
            'id_gasto' => $id,
            'monto' => $gasto->monto,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Gasto eliminado']);
        }

        return redirect()->route('dashboard.index')->with('success', 'Gasto eliminado');
    }

    /** Listado paginado para el modulo de gastos. */
    public function apiList(Request $request): JsonResponse
    {
        $query = DB::table('global.gastos')
            ->leftJoin('global.vehiculos', 'global.gastos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->leftJoin('global.clasificador_gastos', 'global.gastos.id_clasificador', '=', 'global.clasificador_gastos.id_clasificador')
            ->select(
                'global.gastos.*',
                'global.vehiculos.placa_vehiculo as placa',
                'global.clasificador_gastos.codigo as clasificador_codigo',
                'global.clasificador_gastos.descripcion as clasificador_descripcion'
            );

        if ($request->filled('id_vehiculo')) {
            $query->where('global.gastos.id_vehiculo', $request->integer('id_vehiculo'));
        }

        if ($request->filled('tipo_gasto')) {
            $query->where('global.gastos.tipo_gasto', $request->string('tipo_gasto')->value());
        }

        if ($request->filled('es_devolucion')) {
            $query->where('global.gastos.es_devolucion', $request->boolean('es_devolucion'));
        }

        if ($request->filled('desde')) {
            $query->whereDate('global.gastos.fecha_gasto', '>=', $request->date('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('global.gastos.fecha_gasto', '<=', $request->date('hasta'));
        }

        $limite = min($request->integer('limit', 100), 500);

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('global.gastos.fecha_gasto')->limit($limite)->get(),
        ]);
    }

    public function apiShow(string $id): JsonResponse
    {
        $gasto = DB::table('global.gastos')
            ->leftJoin('global.clasificador_gastos', 'global.gastos.id_clasificador', '=', 'global.clasificador_gastos.id_clasificador')
            ->select('global.gastos.*', 'global.clasificador_gastos.codigo as clasificador_codigo', 'global.clasificador_gastos.descripcion as clasificador_descripcion')
            ->where('global.gastos.id_gasto', $id)
            ->first();

        if ($gasto && $gasto->tipo_gasto === TipoGasto::Combustible->value) {
            $gasto->combustible = DB::table('global.combustible_detalle')->where('id_gasto', $id)->first();
        }

        return response()->json(['success' => true, 'data' => $gasto]);
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    /**
     * Catalogos que alimentan el formulario.
     *
     * @return array<string, mixed>
     */
    private function datosDeFormulario(): array
    {
        return [
            'vehiculos' => DB::table('global.vehiculos')
                ->whereIn('estado', [EstadoVehiculo::Activo->value, EstadoVehiculo::Taller->value])
                ->orderByRaw("LPAD(REGEXP_REPLACE(placa_vehiculo, '[^0-9]', '', 'g'), 10, '0')")
                ->get(),
            'proveedores' => DB::table('global.proveedores')
                ->where('estado', 1)->orderBy('nombre_proveedor')->get(),
            'bancos' => DB::table('global.bancos')
                ->where('estado', 'ACTIVO')->orderBy('nombre_banco')->get(),
            // SOLO los clasificadores que aplican a unidad.
            'clasificadores' => $this->clasificadores->listar('unidad'),
        ];
    }

    private function respuesta(Request $request, object $gasto, string $mensaje): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'nro_documento' => $gasto->nro_documento ?? null,
                'id_gasto' => $gasto->id_gasto ?? null,
            ]);
        }

        return redirect()->route('dashboard.index')->with('success', $mensaje);
    }

    private function error(Request $request, string $mensaje, int $status = 422): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $mensaje], $status);
        }

        return back()->with('error', $mensaje);
    }
}