<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoFactura;
use App\Enums\EstadoVehiculo;
use App\Http\Requests\Facturacion\StoreFleteRequest;
use App\Services\AuditoriaService;
use App\Services\DocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * FACTURACION: registro de fletes y su agrupacion en facturas.
 *
 * CAMBIOS RESPECTO A LA VERSION ANTERIOR
 * --------------------------------------
 *  1. Numeracion atomica de la serie I_ (antes: leer el ultimo y sumar 1).
 *  2. `numero_factura` SOLO puede existir si hay fecha de factura: antes se
 *     permitia un numero con fecha NULL, lo que rompia el agrupamiento.
 *  3. El estado arranca siempre en PENDIENTE. Antes `create()` no fijaba
 *     estado y la columna	default 'EMITIDA' producia un estado huerfano
 *     que el listado de pendientes nunca mostraba.
 *  4. El borrado fisico se sustituye por anulacion, coherente con los
 *     reportes que ya excluian el estado ANULADA del balance.
 */
class FacturacionController extends Controller
{
    public function __construct(
        private readonly DocumentoService $documentos,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(): View
    {
        return view('facturacion.index');
    }

    public function create(): View
    {
        return view('facturacion.form', $this->datosDeFormulario(['ingreso' => null]));
    }

    public function edit(string $id): View|RedirectResponse
    {
        $ingreso = DB::table('global.ingresos')->where('id_ingreso', $id)->first();

        if (!$ingreso) {
            return redirect()->route('facturacion.index')->with('error', 'Registro no encontrado.');
        }

        return view('facturacion.form', $this->datosDeFormulario(['ingreso' => $ingreso]));
    }

    public function store(StoreFleteRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $datos = $request->datosNormalizados();

            $datos['nro_documento'] = $this->documentos->siguiente('I');
            $datos['fecha_registro'] = now();

            $id = DB::table('global.ingresos')->insertGetId($datos, 'id_ingreso');

            $this->auditoria->registrar(
                'FLETE_REGISTRADO',
                'facturacion',
                "Flete {$datos['nro_documento']} por Bs. " . number_format((float) $datos['monto'], 2),
                ['id' => $id, 'vehiculo' => $datos['id_vehiculo']]
            );

            $mensaje = sprintf('Flete %s registrado exitosamente', $datos['nro_documento']);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $mensaje,
                    'nro_documento' => $datos['nro_documento'],
                    'id_ingreso' => $id,
                ]);
            }

            return redirect()->route('facturacion.index')->with('success', $mensaje);
        } catch (Throwable $e) {
            report($e);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'No se pudo registrar el flete.'], 500);
            }

            return back()->with('error', 'No se pudo registrar el flete.')->withInput();
        }
    }

    public function update(StoreFleteRequest $request, string $id): RedirectResponse
    {
        if (!$this->puedeEditar($request)) {
            abort(403, 'Su rol no permite modificar fletes.');
        }

        $datos = $request->datosNormalizados();

        // Inmutables: identidad del documento y estado de facturacion.
        unset($datos['estado_factura'], $datos['nro_documento'], $datos['id_tramo']);

        DB::table('global.ingresos')->where('id_ingreso', $id)->update($datos);

        $this->auditoria->registrar('FLETE_ACTUALIZADO', 'facturacion', "Flete #{$id} actualizado", ['id' => $id]);

        return redirect()->route('facturacion.index')->with('success', 'Flete actualizado exitosamente');
    }

    /** Anulacion logica: el flete sale del balance pero conserva su historia. */
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        if (!$request->user()?->can('anular', 'facturacion')) {
            abort(403, 'Su rol no permite anular fletes.');
        }

        DB::table('global.ingresos')
            ->where('id_ingreso', $id)
            ->update(['estado_factura' => EstadoFactura::Anulada->value]);

        $this->auditoria->registrar('FLETE_ANULADO', 'facturacion', "Flete #{$id} anulado", ['id' => $id]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Flete anulado']);
        }

        return redirect()->route('facturacion.index')->with('success', 'Flete anulado');
    }

    // ------------------------------------------------------------------
    // API
    // ------------------------------------------------------------------

    /** Facturas emitidas, agrupadas por numero. */
    public function apiList(): JsonResponse
    {
        $data = DB::table('global.ingresos')
            ->select(
                'numero_factura',
                'fecha_factura',
                'cliente_nombre',
                DB::raw('COUNT(*) as cantidad_fletes'),
                DB::raw('SUM(monto) as total_monto'),
                'estado_factura',
                DB::raw('SUM(toneladas) as toneladas')
            )
            ->whereNotNull('numero_factura')
            ->where('estado_factura', '!=', EstadoFactura::Anulada->value)
            ->groupBy('numero_factura', 'fecha_factura', 'cliente_nombre', 'estado_factura')
            ->orderByDesc('fecha_factura')
            ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** Fletes aun no facturados, paginados. */
    public function apiPendientes(Request $request): JsonResponse
    {
        $query = DB::table('global.ingresos')
            ->leftJoin('global.vehiculos', 'global.ingresos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->leftJoin('global.personal', 'global.ingresos.id_personal', '=', 'global.personal.id_personal')
            ->where('global.ingresos.estado_factura', EstadoFactura::Pendiente->value)
            ->select(
                'global.ingresos.*',
                'global.vehiculos.placa_vehiculo',
                DB::raw("COALESCE(CONCAT(global.personal.nombres, ' ', global.personal.apellidos), '') as conductor")
            );

        if ($request->filled('fecha_inicio')) {
            $query->where('global.ingresos.fecha_ingreso', '>=', $request->date('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->where('global.ingresos.fecha_ingreso', '<=', $request->date('fecha_fin'));
        }

        if ($request->filled('cliente')) {
            $query->where('global.ingresos.cliente_nombre', 'ilike', '%' . $request->string('cliente')->value() . '%');
        }

        $pagina = max(1, $request->integer('page', 1));
        $limite = min(max(1, $request->integer('limit', 50)), 200);
        $total = (clone $query)->count();

        $data = $query->orderByDesc('global.ingresos.fecha_ingreso')
            ->skip(($pagina - 1) * $limite)
            ->take($limite)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $pagina,
                'limit' => $limite,
                'pages' => (int) ceil($total / $limite),
            ],
        ]);
    }

    public function apiFletesByFactura(string $numeroFactura): JsonResponse
    {
        $data = DB::table('global.ingresos')
            ->leftJoin('global.vehiculos', 'global.ingresos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->leftJoin('global.personal', 'global.ingresos.id_personal', '=', 'global.personal.id_personal')
            ->where('global.ingresos.numero_factura', $numeroFactura)
            ->select(
                'global.ingresos.*',
                'global.vehiculos.placa_vehiculo',
                DB::raw("COALESCE(CONCAT(global.personal.nombres, ' ', global.personal.apellidos), '') as conductor_asignado")
            )
            ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** Agrupa varios fletes en una misma factura. */
    public function apiBatchFacturar(Request $request): JsonResponse
    {
        if (!$request->user()?->can('crear', 'facturacion')) {
            return response()->json(['success' => false, 'message' => 'Sin permisos para facturar.'], 403);
        }

        $validado = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            // Sin el prefijo `global.`: Laravel parsea `exists:global.ingresos` como
// conexion "global" + tabla "ingresos" y falla con "Database connection
// [global] not configured". El esquema ya viene resuelto por el
// `search_path=global` de config/database.php.
'ids.*' => ['integer', 'exists:ingresos,id_ingreso'],
            'numero_factura' => ['required', 'string', 'max:50'],
            'fecha_factura' => ['required', 'date'],
            'cliente_nombre' => ['required', 'string', 'max:200'],
        ]);

        $ids = array_map('intval', $validado['ids']);

        // Solo se facturan fletes que siguen PENDIENTE: evita facturar dos
        // veces por una doble pulsacion o un reintento de red.
        $afectados = DB::table('global.ingresos')
            ->whereIn('id_ingreso', $ids)
            ->where('estado_factura', EstadoFactura::Pendiente->value)
            ->update([
                'numero_factura' => $validado['numero_factura'],
                'fecha_factura' => $validado['fecha_factura'],
                'cliente_nombre' => $validado['cliente_nombre'],
                'estado_factura' => EstadoFactura::Facturada->value,
            ]);

        $this->auditoria->registrar('LOTE_FACTURADO', 'facturacion', "Factura {$validado['numero_factura']}", [
            'fletes' => $afectados,
            'factura' => $validado['numero_factura'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$afectados} flete(s) facturados exitosamente",
            'facturados' => $afectados,
        ]);
    }

    /** Marca una factura como cobrada (o la devuelve a facturada). */
    public function apiToggleCobrado(Request $request): JsonResponse
    {
        $validado = $request->validate([
            'numero_factura' => ['required', 'string', 'max:50'],
            'estado' => ['required', 'string', 'in:' . EstadoFactura::Cobrada->value . ',' . EstadoFactura::Facturada->value],
        ]);

        $afectados = DB::table('global.ingresos')
            ->where('numero_factura', $validado['numero_factura'])
            ->update(['estado_factura' => $validado['estado']]);

        $this->auditoria->registrar(
            'FACTURA_ESTADO_CAMBIADO',
            'facturacion',
            "Factura {$validado['numero_factura']} -> {$validado['estado']}",
            ['factura' => $validado['numero_factura'], 'estado' => $validado['estado']]
        );

        return response()->json(['success' => true, 'message' => 'Estado actualizado', 'afectados' => $afectados]);
    }

    public function apiAll(Request $request): JsonResponse
    {
        $query = DB::table('global.ingresos')
            ->leftJoin('global.vehiculos', 'global.ingresos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->leftJoin('global.personal', 'global.ingresos.id_personal', '=', 'global.personal.id_personal')
            ->select(
                'global.ingresos.*',
                'global.vehiculos.placa_vehiculo',
                DB::raw("COALESCE(CONCAT(global.personal.nombres, ' ', global.personal.apellidos), '') as conductor_nombre")
            );

        if ($request->filled('estado')) {
            $query->where('global.ingresos.estado_factura', $request->string('estado')->value());
        }

        if ($request->filled('fecha_inicio')) {
            $query->where('global.ingresos.fecha_ingreso', '>=', $request->date('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->where('global.ingresos.fecha_ingreso', '<=', $request->date('fecha_fin'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('global.ingresos.fecha_ingreso')->get(),
        ]);
    }

    public function apiShow(string $id): JsonResponse
    {
        $ingreso = DB::table('global.ingresos')
            ->leftJoin('global.vehiculos', 'global.ingresos.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->leftJoin('global.personal', 'global.ingresos.id_personal', '=', 'global.personal.id_personal')
            ->select(
                'global.ingresos.*',
                'global.vehiculos.placa_vehiculo',
                DB::raw("COALESCE(CONCAT(global.personal.nombres, ' ', global.personal.apellidos), '') as conductor_nombre")
            )
            ->where('global.ingresos.id_ingreso', $id)
            ->first();

        return response()->json(['success' => true, 'data' => $ingreso]);
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    private function puedeEditar(Request $request): bool
    {
        return (bool) $request->user()?->can('editar', 'facturacion');
    }

    /** @return array<string, mixed> */
    private function datosDeFormulario(array $extra = []): array
    {
        return $extra + [
            'vehiculos' => DB::table('global.vehiculos')
                ->whereIn('estado', [
                    EstadoVehiculo::Activo->value,
                    EstadoVehiculo::Taller->value,
                ])
                ->orderByRaw("LPAD(REGEXP_REPLACE(placa_vehiculo, '[^0-9]', '', 'g'), 10, '0')")
                ->get(),
            'personal' => DB::table('global.personal')
                ->where('estado', 1)
                ->select('global.personal.*', DB::raw("CONCAT(nombres, ' ', apellidos) as nombre_completo"))
                ->orderBy('nombres')
                ->get(),
            'tramos' => DB::table('global.tramos')->orderBy('origen')->get(),
            'config' => DB::table('global.configuracion')->pluck('valor', 'llave'),
        ];
    }
}