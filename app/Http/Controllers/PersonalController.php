<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoVehiculo;
use App\Enums\TipoGasto;
use App\Http\Requests\StorePersonalRequest;
use App\Services\AuditoriaService;
use App\Services\DocumentoService;
use App\Services\GastoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * PERSONAL: conductores y administrativos, con registros de sueldo y viatico.
 *
 * CAMBIOS RESPECTO A LA VERSION ANTERIOR
 * --------------------------------------
 *  1. `storeGasto` validaba `in:Sueldo,Viǭtico`: la palabra "Viático" venia
 *     con un caracter corrupto, de modo que ese valor NUNCA pasaba la
 *     validacion y el registro de viáticos era imposible.
 *  2. El tipo se toma del enum canonico en singular, igual que gastos.
 *  3. Se respeta el rol de quien registra: un lector no puede cargar sueldos.
 */
class PersonalController extends Controller
{
    public function __construct(
        private readonly DocumentoService $documentos,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(): View
    {
        return view('personal.index');
    }

    public function create(): View
    {
        return view('personal.form', ['personal' => null]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $personal = DB::table('global.personal')->where('id_personal', $id)->first();

        if (!$personal) {
            return redirect()->route('personal.index')->with('error', 'Personal no encontrado.');
        }

        return view('personal.form', ['personal' => $personal]);
    }

    public function store(StorePersonalRequest $request): RedirectResponse
    {
        DB::table('global.personal')->insert($request->datosNormalizados());

        $this->auditoria->registrar('PERSONAL_CREADO', 'personal', 'Personal registrado', []);

        return redirect()->route('personal.index')->with('success', 'Personal registrado exitosamente');
    }

    public function update(StorePersonalRequest $request, string $id): RedirectResponse
    {
        DB::table('global.personal')
            ->where('id_personal', $id)
            ->update($request->datosActualizables());

        $this->auditoria->registrar('PERSONAL_ACTUALIZADO', 'personal', "Personal #{$id} actualizado", ['id' => $id]);

        return redirect()->route('personal.index')->with('success', 'Personal actualizado exitosamente');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        if (!$request->user()?->can('eliminar', 'personal')) {
            abort(403, 'Su rol no permite eliminar personal.');
        }

        // Con vehiculos asignados solo se desactiva: borrarlo dejaria
        // unidades huerfanas en la columna id_personal.
        $tieneVehiculos = DB::table('global.vehiculos')->where('id_personal', $id)->exists();

        if ($tieneVehiculos) {
            DB::table('global.personal')->where('id_personal', $id)->update(['estado' => 0]);

            return redirect()->route('personal.index')
                ->with('success', 'Personal desactivado (tiene vehiculos asignados).');
        }

        DB::table('global.personal')->where('id_personal', $id)->delete();

        $this->auditoria->registrar('PERSONAL_ELIMINADO', 'personal', "Personal #{$id} eliminado", ['id' => $id]);

        return redirect()->route('personal.index')->with('success', 'Personal eliminado');
    }

    public function sueldo(string $id): View|RedirectResponse
    {
        $personal = DB::table('global.personal')->where('id_personal', $id)->first();

        if (!$personal) {
            return redirect()->route('personal.index')->with('error', 'Personal no encontrado.');
        }

        return view('personal.sueldo', compact('personal'));
    }

    public function viatico(string $id): View|RedirectResponse
    {
        $personal = DB::table('global.personal')->where('id_personal', $id)->first();

        if (!$personal) {
            return redirect()->route('personal.index')->with('error', 'Personal no encontrado.');
        }

        $vehiculos = DB::table('global.vehiculos')
            ->where('estado', EstadoVehiculo::Activo->value)
            ->orderByRaw("LPAD(REGEXP_REPLACE(placa_vehiculo, '[^0-9]', '', 'g'), 10, '0')")
            ->get();

        return view('personal.viatico', compact('personal', 'vehiculos'));
    }

    /**
     * Registra un sueldo o un viatico.
     *
     * Un monto NEGATIVO es una devolucion, y asi se comunica al usuario.
     */
    public function storeGasto(Request $request): RedirectResponse
    {
        if (!$request->user()?->can('crear', 'gastos')) {
            abort(403, 'Su rol no permite registrar pagos a personal.');
        }

        $validado = $request->validate([
            'id_personal' => ['required', 'integer', 'exists:personal,id_personal'],
            // Enum canonico: el bug anterior usaba 'Viǭtico', corrupto.
            'tipo_gasto' => ['required', 'string', 'in:' . TipoGasto::Sueldo->value . ',' . TipoGasto::Viatico->value],
            'tipo_viatico' => ['nullable', 'string', 'in:LOCAL,VIAJE'],
            'concepto' => ['required', 'string', 'max:200'],
            'monto' => ['required', 'numeric', 'not_in:0'],
            'fecha_gasto' => ['required', 'date'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'id_vehiculo' => ['nullable', 'integer', 'exists:vehiculos,id_vehiculo'],
            'destino_viatico' => ['nullable', 'string', 'max:200'],
        ]);

        $monto = round((float) $validado['monto'], 2);
        $esDevolucion = GastoService::esDevolucion($monto);

        // Localiza el clasificador de alcance unidad para el tipo indicado.
        $clasificadorId = DB::table('global.clasificador_gastos')
            ->where('tipo_gasto', $validado['tipo_gasto'])
            ->where('afecta_unidad', true)
            ->where('estado', 'ACTIVO')
            ->orderBy('id_clasificador')
            ->value('id_clasificador');

        DB::table('global.gastos')->insert([
            'id_vehiculo' => $validado['id_vehiculo'] ?? null,
            'id_personal' => $validado['id_personal'],
            'id_clasificador' => $clasificadorId,
            'tipo_gasto' => $validado['tipo_gasto'],
            'tipo_viatico' => $validado['tipo_viatico'] ?? null,
            'concepto' => $validado['concepto'],
            'monto' => $monto,
            'fecha_gasto' => $validado['fecha_gasto'],
            'descripcion' => $validado['descripcion'] ?? null,
            'destino_viatico' => $validado['destino_viatico'] ?? null,
            'nro_documento' => $this->documentos->siguiente('E'),
            'condicion_pago' => 'CONTADO',
            'es_devolucion' => $esDevolucion,
            'estado_pago' => $esDevolucion ? 'Anulado' : 'Pagado',
            'creado_por' => $request->user()?->id,
            'fecha_registro' => now(),
            'fecha_actualizacion' => now(),
        ]);

        $this->auditoria->registrar(
            $esDevolucion ? 'DEVOLUCION_PERSONAL' : 'PAGO_PERSONAL',
            'personal',
            sprintf('%s Bs. %s', $validado['tipo_gasto'], number_format($monto, 2)),
            ['id_personal' => $validado['id_personal'], 'devolucion' => $esDevolucion]
        );

        $personal = DB::table('global.personal')->where('id_personal', $validado['id_personal'])->first();
        $nombre = trim(($personal->nombres ?? '') . ' ' . ($personal->apellidos ?? ''));

        $mensaje = $esDevolucion
            ? "Devolucion de {$validado['tipo_gasto']} de {$nombre} registrada exitosamente"
            : ucfirst(strtolower($validado['tipo_gasto'])) . " de {$nombre} registrado exitosamente";

        return redirect()->route('personal.index')->with('success', $mensaje);
    }

    public function apiList(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.personal')
                ->orderBy('nombres')
                ->get(),
        ]);
    }

    public function apiShow(string $id): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.personal')->where('id_personal', $id)->first(),
        ]);
    }
}