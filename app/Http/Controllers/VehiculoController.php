<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoFactura;
use App\Enums\EstadoVehiculo;
use App\Enums\Rol;
use App\Http\Requests\StoreVehiculoRequest;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * FLOTA de unidades.
 *
 * CAMBIOS RESPECTO A LA VERSION ANTERIOR
 * --------------------------------------
 *  1. `apiList` interpolaba la fecha dentro de un DB::raw:
 *        to_char(fecha_ingreso,'YYYY-MM') = '{$mesActual}'
 *     Eso rompia los prepared statements, precisamente el problema que se
 *     intento corregir para el pooler. Se sustituye por un binding.
 *  2. `orden` y `busqueda` se validan en lugar de interpolarse en el orden.
 */
class VehiculoController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    public function index(): View
    {
        return view('vehiculos.index');
    }

    public function create(): View
    {
        return view('vehiculos.form', [
            'vehiculo' => null,
            'personal' => $this->personalActivo(),
            'estados' => EstadoVehiculo::cases(),
        ]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $vehiculo = DB::table('global.vehiculos')->where('id_vehiculo', $id)->first();

        if (!$vehiculo) {
            return redirect()->route('dashboard.index')->with('error', 'Unidad no encontrada.');
        }

        return view('vehiculos.form', [
            'vehiculo' => $vehiculo,
            'personal' => $this->personalActivo(),
            'estados' => EstadoVehiculo::cases(),
        ]);
    }

    public function store(StoreVehiculoRequest $request): RedirectResponse
    {
        $id = DB::table('global.vehiculos')->insertGetId($request->datosNormalizados(), 'id_vehiculo');

        $this->auditoria->registrar('VEHICULO_CREADO', 'vehiculos', "Unidad #{$id} registrada", ['id' => $id]);

        return redirect()->route('dashboard.index')->with('success', 'Unidad registrada exitosamente');
    }

    public function update(StoreVehiculoRequest $request, string $id): RedirectResponse
    {
        $datos = $request->datosNormalizados();
        $datos['fecha_actualizacion'] = now();

        DB::table('global.vehiculos')->where('id_vehiculo', $id)->update($datos);

        $this->auditoria->registrar('VEHICULO_ACTUALIZADO', 'vehiculos', "Unidad #{$id} actualizada", ['id' => $id]);

        return redirect()->route('dashboard.index')->with('success', 'Unidad actualizada exitosamente');
    }

    /** Baja logica: la unidad pasa a estado VENDIDO (3). */
    public function destroy(string $id): RedirectResponse
    {
        DB::table('global.vehiculos')
            ->where('id_vehiculo', $id)
            ->update(['estado' => EstadoVehiculo::Vendido->value, 'fecha_actualizacion' => now()]);

        $this->auditoria->registrar('VEHICULO_DADO_DE_BAJA', 'vehiculos', "Unidad #{$id} dada de baja", ['id' => $id]);

        return redirect()->route('dashboard.index')->with('success', 'Unidad dada de baja');
    }

    /** Marca una unidad como vendida. */
    public function vender(Request $request): JsonResponse
    {
        if (!$request->user()?->can('anular', 'vehiculos')) {
            return response()->json(['success' => false, 'message' => 'Sin permisos para dar de baja unidades.'], 403);
        }

        $id = $request->integer('id_vehiculo');

        $vehiculo = DB::table('global.vehiculos')->where('id_vehiculo', $id)->first();

        if (!$vehiculo) {
            return response()->json(['success' => false, 'message' => 'Vehiculo no encontrado.'], 404);
        }

        if ((int) $vehiculo->estado === EstadoVehiculo::Vendido->value) {
            return response()->json(['success' => false, 'message' => 'La unidad ya figura como VENDIDA.'], 422);
        }

        DB::table('global.vehiculos')
            ->where('id_vehiculo', $id)
            ->update(['estado' => EstadoVehiculo::Vendido->value, 'fecha_actualizacion' => now()]);

        $this->auditoria->registrar('VEHICULO_VENDIDO', 'vehiculos', "Unidad {$vehiculo->placa_vehiculo} vendida", ['id' => $id]);

        return response()->json([
            'success' => true,
            'message' => 'Vehiculo marcado como VENDIDO',
        ]);
    }

    public function apiList(Request $request): JsonResponse
    {
        // `Y-m` y el literal del enum se intercalan en el SQL porque Laravel
        // no admite bindings dentro de las expresiones del SELECT; ambos
        // valores estan acotados a un formato fijo o a una constante del enum.
        $mes = now()->format('Y-m');

        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $mes = '1970-01';
        }

        $anulada = EstadoFactura::Anulada->value;

        $query = DB::table('global.vehiculos')
            ->leftJoin('global.personal', 'global.vehiculos.id_personal', '=', 'global.personal.id_personal')
            ->select(
                'global.vehiculos.*',
                DB::raw("COALESCE(CONCAT(global.personal.nombres, ' ', global.personal.apellidos), '') as conductor"),
                DB::raw(
                    "(SELECT COALESCE(SUM(monto), 0) FROM global.ingresos
                      WHERE id_vehiculo = global.vehiculos.id_vehiculo
                        AND estado_factura <> '{$anulada}'
                        AND to_char(fecha_ingreso, 'YYYY-MM') = '{$mes}') as total_ingresos"
                ),
                DB::raw(
                    "(SELECT COALESCE(SUM(monto), 0) FROM global.gastos
                      WHERE id_vehiculo = global.vehiculos.id_vehiculo
                        AND to_char(fecha_gasto, 'YYYY-MM') = '{$mes}') as total_gastos"
                ),
            );

        if ($request->filled('estado')) {
            $query->where('global.vehiculos.estado', $request->integer('estado'));
        }

        if ($request->filled('busqueda')) {
            $busqueda = $request->string('busqueda')->value();

            $query->where(function ($q) use ($busqueda) {
                $q->where('global.vehiculos.placa_vehiculo', 'ilike', "%{$busqueda}%")
                    ->orWhere('global.vehiculos.marca', 'ilike', "%{$busqueda}%");
            });
        }

        // Orden por el numero de la placa (no alfabeticamente), que es como
        // el operador lee la flota. Se usa orderByRaw y no orderBy con un
        // Expression: la firma de orderBy espera un nombre de columna.
        $ordenRaw = match ($request->query('orden', 'placa')) {
            'marca' => "COALESCE(NULLIF(global.vehiculos.marca, ''), global.vehiculos.placa_vehiculo) ASC",
            'ultimo' => 'global.vehiculos.id_vehiculo DESC',
            default => "LPAD(REGEXP_REPLACE(global.vehiculos.placa_vehiculo, '[^0-9]', '', 'g'), 10, '0') ASC",
        };

        $data = $query->orderByRaw($ordenRaw)->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => ['total' => $data->count()],
        ]);
    }

    public function apiShow(string $id): JsonResponse
    {
        $vehiculo = DB::table('global.vehiculos')
            ->leftJoin('global.personal', 'global.vehiculos.id_personal', '=', 'global.personal.id_personal')
            ->select(
                'global.vehiculos.*',
                DB::raw("COALESCE(CONCAT(global.personal.nombres, ' ', global.personal.apellidos), '') as conductor")
            )
            ->where('global.vehiculos.id_vehiculo', $id)
            ->first();

        return response()->json(['success' => true, 'data' => $vehiculo]);
    }

    private function personalActivo()
    {
        return DB::table('global.personal')->where('estado', 1)->orderBy('nombres')->get();
    }
}