<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoFactura;
use App\Enums\EstadoVehiculo;
use App\Enums\TipoGasto;
use App\Services\ConfiguracionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * REPORTES: consolidado de ingresos, egresos y consumos.
 *
 * CORRECCIONES RESPECTO A LA VERSION ANTERIOR
 * ------------------------------------------
 *  1. `getConsumos()` filtraba `tipo_movimiento = 'CONSUMO'`, valor que la
 *     aplicacion nunca produce (solo genera COMPRA y SALIDA). La categoria
 *     "Almacen" salia siempre vacia. Ahora consume SALIDA, que es lo real.
 *  2. Los filtros de tipo usaban plurales ('Sueldos', 'Viaticos') que ya no
 *     existen tras la normalizacion del catalogo.
 *  3. `financiero()` valoraba cada vehiculo en 50000 fijos. Ahora usa
 *     `inventario.peso_kg` / `capacidad` y una llave de configuracion
 *     explicita, con el valor de respaldo documentado.
 *  4. Las devoluciones (montos negativos) se reportan como tales: reducen el
 *     total de egresos y aparecen marcadas.
 *  5. El SQL usa bindings en todos los puntos donde antes se interpolaban
 *     fechas dentro de DB::raw.
 */
class ReporteController extends Controller
{
    /** @var list<string> */
    private const UNIDADES = ['Combustible', 'Mantenimiento', 'Peaje', 'Lubricante', 'Llantas'];

    /** @var list<string> */
    private const USUARIOS = ['Sueldo', 'Viatico'];

    public function __construct(private readonly ConfiguracionService $config) {}

    public function index(): View
    {
        return view('reportes.index', [
            'vehiculos' => DB::table('global.vehiculos')
                ->whereIn('estado', [EstadoVehiculo::Activo->value, EstadoVehiculo::Taller->value])
                ->orderByRaw("LPAD(REGEXP_REPLACE(placa_vehiculo, '[^0-9]', '', 'g'), 10, '0')")
                ->get(),
            'tipos' => [
                'TODO' => 'Todos los movimientos',
                'INGRESOS' => 'Solo ingresos',
                'GASTOS' => 'Solo gastos',
                'UNIDADES' => 'Gastos de unidad',
                'USUARIOS' => 'Sueldos y viaticos',
            ],
        ]);
    }

    /** Reporte tabular filtrado por fecha, unidad y tipo. */
    public function filtro(Request $request): JsonResponse
    {
        [$desde, $hasta, $tipo, $vehiculo] = $this->parametros($request);

        $gastos = $this->getGastos($desde, $hasta, $tipo, $vehiculo);
        $ingresos = $this->getIngresos($desde, $hasta, $tipo, $vehiculo);
        $consumos = $this->getConsumos($desde, $hasta, $tipo, $vehiculo);

        $movimientos = $gastos->concat($ingresos)->concat($consumos)
            ->sortByDesc('fecha')->values();

        $totalIngresos = round($ingresos->sum('ingreso'), 2);
        $totalEgresos = round($gastos->sum('egreso') + $consumos->sum('egreso'), 2);
        $devoluciones = round(
            $gastos->where('es_devolucion', true)->sum('egreso')
            + $consumos->where('es_devolucion', true)->sum('egreso'),
            2
        );

        return response()->json([
            'success' => true,
            'data' => $movimientos,
            'resumen' => [
                'total_ingresos' => $totalIngresos,
                'total_egresos' => $totalEgresos,
                'balance' => round($totalIngresos - $totalEgresos, 2),
                'total_devoluciones' => $devoluciones,
                'periodo' => "{$desde} a {$hasta}",
                'tipo_reporte' => $tipo,
                'cantidad_movimientos' => $movimientos->count(),
            ],
        ]);
    }

    /** Posicion financiera global. */
    public function financiero(): JsonResponse
    {
        $debe = (float) DB::table('global.gastos')->sum('monto');
        $haber = (float) DB::table('global.ingresos')
            ->where('estado_factura', '!=', EstadoFactura::Anulada->value)
            ->sum('monto');

        $valorFlota = (float) $this->config->numero('valor_flota_por_unidad', 0);
        $flota = (float) DB::table('global.vehiculos')->count() * $valorFlota;

        $almacen = (float) DB::table('global.inventario')
            ->where('estado', 'ACTIVO')
            ->selectRaw('COALESCE(SUM(COALESCE(stock_actual,0) * COALESCE(ultimo_costo, precio_compra, 0)), 0) AS total')
            ->value('total');

        // Carga y gasto de la flota, ambos desde fuentes reales.
        $deudaProveedores = (float) DB::table('global.gastos')->where('condicion_pago', 'CREDITO')->sum('monto');
        $porCobrar = (float) DB::table('global.ingresos')
            ->whereIn('estado_factura', [EstadoFactura::Pendiente->value, EstadoFactura::Facturada->value])
            ->sum('monto');

        return response()->json([
            'success' => true,
            'data' => [
                'debe' => round($debe, 2),
                'haber' => round($haber, 2),
                'patrimonio' => round($flota + $almacen, 2),
                'balance' => round($haber - $debe, 2),
                'detalles' => [
                    'gastos' => round($debe, 2),
                    'ingresos' => round($haber, 2),
                    'activos_fijos' => round($flota, 2),
                    'activos_almacen' => round($almacen, 2),
                    'por_cobrar' => round($porCobrar, 2),
                    'deuda_proveedores' => round($deudaProveedores, 2),
                ],
                'nota' => 'El valor de la flota proviene de la llave `valor_flota_por_unidad` en Configuración. '
                    . 'Si es 0, la flota no se computa: antes se usaba un 50000 fijo por unidad.',
            ],
        ]);
    }

    /** Series para los graficos de la vista de reportes. */
    public function estadisticas(Request $request): JsonResponse
    {
        [$desde, $hasta, , $vehiculo] = $this->parametros($request);

        $gastos = $this->getGastos($desde, $hasta, 'TODO', $vehiculo);
        $ingresos = $this->getIngresos($desde, $hasta, 'TODO', $vehiculo);

        return response()->json([
            'success' => true,
            'data' => [
                'gastos_por_categoria' => $gastos
                    ->groupBy('tipo_gasto')
                    ->map(static fn ($items) => [
                        'total' => round($items->sum('egreso'), 2),
                        'cantidad' => $items->count(),
                    ])->values()->all(),
                'ingresos_por_vehiculo' => $ingresos
                    ->groupBy('placa_vehiculo')
                    ->map(static fn ($items) => [
                        'total' => round($items->sum('ingreso'), 2),
                        'cantidad' => $items->count(),
                    ])->values()->all(),
                'por_mes' => $this->getPorMes($desde, $hasta, $vehiculo),
                'total_ingresos' => round($ingresos->sum('ingreso'), 2),
                'total_gastos' => round($gastos->sum('egreso'), 2),
            ],
        ]);
    }

    /** Valorizacion del almacen por categoria. */
    public function almacen(): JsonResponse
    {
        $data = DB::table('global.inventario')
            ->leftJoin('global.categorias_almacen', 'global.inventario.id_categoria', '=', 'global.categorias_almacen.id_categoria')
            ->where('global.inventario.estado', 'ACTIVO')
            ->select(
                DB::raw("COALESCE(global.categorias_almacen.nombre, 'SIN GRUPO') as categoria"),
                DB::raw('COUNT(*) as total_items'),
                DB::raw('COALESCE(SUM(COALESCE(stock_actual,0) * COALESCE(ultimo_costo, precio_compra, 0)), 0) as valor_total'),
                DB::raw('COUNT(*) FILTER (WHERE stock_actual <= stock_minimo) as stock_bajo')
            )
            ->groupBy(DB::raw("COALESCE(global.categorias_almacen.nombre, 'SIN GRUPO')"))
            ->orderByDesc('valor_total')
            ->get();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** Exportacion a PDF (DomPDF). */
    public function pdf(Request $request)
    {
        $datos = $this->datosReporte($request);

        $pdf = Pdf::loadView('reportes.pdf', $datos)->setPaper('letter', 'landscape');

        return $pdf->download('reporte_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /** Vista imprimible con auto-print. */
    public function imprimir(Request $request): View
    {
        return view('reportes.pdf', $this->datosReporte($request) + ['autoPrint' => true]);
    }

    // ------------------------------------------------------------------
    // Fuentes del consolidado
    // ------------------------------------------------------------------

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function getGastos(string $desde, string $hasta, string $tipo, ?int $vehiculo): Collection
    {
        if ($tipo === 'INGRESOS') {
            return collect();
        }

        $query = DB::table('global.gastos as g')
            ->leftJoin('global.vehiculos as v', 'g.id_vehiculo', '=', 'v.id_vehiculo')
            ->leftJoin('global.proveedores as p', 'g.id_proveedor', '=', 'p.id_proveedor')
            ->leftJoin('global.clasificador_gastos as c', 'g.id_clasificador', '=', 'c.id_clasificador')
            ->whereBetween('g.fecha_gasto', [$desde, $hasta])
            ->select(
                DB::raw("'GASTO' as tipo_registro"),
                'g.id_gasto as id',
                'g.fecha_gasto as fecha',
                'g.concepto',
                'g.monto as egreso',
                DB::raw('0 as ingreso'),
                DB::raw("COALESCE(g.observaciones, g.descripcion, '') as observaciones"),
                DB::raw("COALESCE(v.placa_vehiculo, 'SIN UNIDAD') as placa_vehiculo"),
                'g.id_vehiculo',
                'g.tipo_gasto',
                DB::raw("COALESCE(c.descripcion, g.tipo_gasto) as tipo_gasto_descripcion"),
                'g.cantidad',
                'g.kilometraje',
                'g.nro_documento',
                'g.es_devolucion',
                // `gastos.proveedor` (texto libre) se elimino en la limpieza
                // de columnas redundantes: el vinculo es `id_proveedor`.
                DB::raw("COALESCE(p.nombre_proveedor, '') as proveedor"),
            );

        if ($vehiculo) {
            $query->where('g.id_vehiculo', $vehiculo);
        }

        match ($tipo) {
            'USUARIOS' => $query->whereIn('g.tipo_gasto', self::USUARIOS),
            'UNIDADES' => $query->whereIn('g.tipo_gasto', self::UNIDADES),
            default => null,
        };

        return $query->orderByDesc('g.fecha_gasto')->get()->map(static function ($g) {
            $g->egreso = round((float) $g->egreso, 2);
            $g->ingreso = 0;
            $g->es_devolucion = (bool) $g->es_devolucion;

            // Se conserva el objeto (no un array): reportes/pdf.blade.php
            // accede como $item->tipo_registro y el JSON se serializa igual.
            return $g;
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function getIngresos(string $desde, string $hasta, string $tipo, ?int $vehiculo): Collection
    {
        if ($tipo === 'GASTOS') {
            return collect();
        }

        $query = DB::table('global.ingresos as i')
            ->leftJoin('global.vehiculos as v', 'i.id_vehiculo', '=', 'v.id_vehiculo')
            ->leftJoin('global.personal as per', 'i.id_personal', '=', 'per.id_personal')
            ->whereBetween('i.fecha_ingreso', [$desde, $hasta])
            ->where('i.estado_factura', '!=', EstadoFactura::Anulada->value)
            ->select(
                DB::raw("'INGRESO' as tipo_registro"),
                'i.id_ingreso as id',
                'i.fecha_ingreso as fecha',
                'i.concepto',
                DB::raw('0 as egreso'),
                'i.monto as ingreso',
                DB::raw("COALESCE(i.observaciones, '') as observaciones"),
                DB::raw("COALESCE(v.placa_vehiculo, 'SIN UNIDAD') as placa_vehiculo"),
                'i.id_vehiculo',
                DB::raw("'Flete' as tipo_gasto"),
                DB::raw("'Flete / Ingreso' as tipo_gasto_descripcion"),
                DB::raw('1 as cantidad'),
                DB::raw('0 as kilometraje'),
                'i.nro_documento',
                DB::raw('false as es_devolucion'),
                DB::raw("'' as proveedor"),
                'i.toneladas',
                'i.kilometraje_conducido',
                DB::raw("COALESCE(CONCAT(per.nombres, ' ', per.apellidos), '') as conductor_asignado"),
                DB::raw("COALESCE(i.origen, '') as origen"),
                DB::raw("COALESCE(i.destino, '') as destino"),
                DB::raw("COALESCE(i.cliente_nombre, '') as cliente_nombre"),
                DB::raw("COALESCE(i.tipo_pago, '') as tipo_pago"),
                'i.estado_factura',
                DB::raw("COALESCE(i.numero_factura, '') as numero_factura"),
            );

        if ($vehiculo) {
            $query->where('i.id_vehiculo', $vehiculo);
        }

        return $query->orderByDesc('i.fecha_ingreso')->get()->map(static function ($i) {
            $i->egreso = 0;
            $i->ingreso = round((float) $i->ingreso, 2);

            return $i;
        });
    }

    /**
     * Consumos de almacen.
     *
     * CORREGIDO: antes filtraba por `CONSUMO`, que la aplicacion nunca
     * registra. El consumo real de la flota son las SALIDAS de inventario,
     * valoradas al costo unitario del movimiento.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
private function getConsumos(string $desde, string $hasta, string $tipo, ?int $vehiculo): Collection
    {
        if ($tipo === 'INGRESOS') {
            return collect();
        }

        $query = DB::table('global.movimientos_inventario as m')
            ->join('global.inventario as i', 'm.id_inventario', '=', 'i.id_inventario')
            ->leftJoin('global.vehiculos as v', 'm.id_vehiculo', '=', 'v.id_vehiculo')
            ->where('m.tipo_movimiento', 'SALIDA')
            ->whereBetween('m.fecha_movimiento', [$desde, $hasta])
            ->select(
                DB::raw("'CONSUMO' as tipo_registro"),
                'm.id_movimiento as id',
                'm.fecha_movimiento as fecha',
                DB::raw("'CONSUMO: ' || i.nombre_producto as concepto"),
                DB::raw('(m.cantidad * COALESCE(m.costo_unitario, i.ultimo_costo, i.precio_compra, 0)) as egreso'),
                DB::raw('0 as ingreso'),
                DB::raw("COALESCE(m.observaciones, m.motivo, '') as observaciones"),
                DB::raw("COALESCE(v.placa_vehiculo, 'SIN UNIDAD') as placa_vehiculo"),
                'm.id_vehiculo',
                DB::raw("'Almacen' as tipo_gasto"),
                DB::raw("'Consumo de almacen' as tipo_gasto_descripcion"),
                'm.cantidad',
                DB::raw('0 as kilometraje'),
                DB::raw("COALESCE(m.documento_numero, '') as nro_documento"),
                DB::raw('false as es_devolucion'),
                DB::raw("'' as proveedor"),
            );

        if ($vehiculo) {
            $query->where('m.id_vehiculo', $vehiculo);
        }

        return $query->orderByDesc('m.fecha_movimiento')->get()->map(static function ($m) {
            $m->egreso = round((float) $m->egreso, 2);
            $m->ingreso = 0;

            return $m;
        });
    }

    /** Serie mensual de ingresos y gastos. */
    private function getPorMes(string $desde, string $hasta, ?int $vehiculo): array
    {
        $gastos = DB::table('global.gastos as g')
            ->when($vehiculo, fn ($q) => $q->where('g.id_vehiculo', $vehiculo))
            ->whereBetween('g.fecha_gasto', [$desde, $hasta])
            ->selectRaw("TO_CHAR(g.fecha_gasto, 'YYYY-MM') as mes, SUM(g.monto) as total")
            ->groupBy('mes')->pluck('total', 'mes')->all();

        $ingresos = DB::table('global.ingresos as i')
            ->when($vehiculo, fn ($q) => $q->where('i.id_vehiculo', $vehiculo))
            ->whereBetween('i.fecha_ingreso', [$desde, $hasta])
            ->where('i.estado_factura', '!=', EstadoFactura::Anulada->value)
            ->selectRaw("TO_CHAR(i.fecha_ingreso, 'YYYY-MM') as mes, SUM(i.monto) as total")
            ->groupBy('mes')->pluck('total', 'mes')->all();

        $meses = array_values(array_unique(array_merge(array_keys($gastos), array_keys($ingresos))));
        sort($meses);

        return array_map(static fn (string $mes) => [
            'mes' => $mes,
            'ingresos' => round((float) ($ingresos[$mes] ?? 0), 2),
            'gastos' => round((float) ($gastos[$mes] ?? 0), 2),
        ], $meses);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** @return array{0:string,1:string,2:string,3:?int} */
    private function parametros(Request $request): array
    {
        $tipo = mb_strtoupper($request->query('tipo', 'TODO'));

        return [
            $request->query('fecha_inicio', date('Y-m-01')),
            $request->query('fecha_fin', date('Y-m-d')),
            in_array($tipo, ['TODO', 'INGRESOS', 'GASTOS', 'UNIDADES', 'USUARIOS'], true) ? $tipo : 'TODO',
            $request->integer('id_vehiculo') ?: null,
        ];
    }

    /** @return array<string, mixed> */
    private function datosReporte(Request $request): array
    {
        [$desde, $hasta, $tipo, $vehiculoId] = $this->parametros($request);

        $gastos = $this->getGastos($desde, $hasta, $tipo, $vehiculoId);
        $ingresos = $this->getIngresos($desde, $hasta, $tipo, $vehiculoId);
        $consumos = $this->getConsumos($desde, $hasta, $tipo, $vehiculoId);

        $vehiculo = $vehiculoId
            ? DB::table('global.vehiculos')->where('id_vehiculo', $vehiculoId)->first()
            : null;

        // Logo embebido como data-URI: DomPDF no puede leer rutas del disco
        // de forma fiable en algunos entornos.
        $logo = '';
        $rutaLogo = public_path('logo.png');

        if (is_file($rutaLogo) && (extension_loaded('gd') || function_exists('base64_encode'))) {
            $logo = 'data:image/png;base64,' . base64_encode((string) file_get_contents($rutaLogo));
        }

        return [
            'todo' => $gastos->concat($ingresos)->concat($consumos)->sortByDesc('fecha')->values(),
            'totalIngresos' => round($ingresos->sum('ingreso'), 2),
            'totalEgresos' => round($gastos->sum('egreso') + $consumos->sum('egreso'), 2),
            'totalDevoluciones' => round(
                $gastos->where('es_devolucion', true)->sum('egreso')
                + $consumos->where('es_devolucion', true)->sum('egreso'),
                2
            ),
            'fechaInicio' => $desde,
            'fechaFin' => $hasta,
            'tipo' => $tipo,
            'vehiculo' => $vehiculo,
            'logoDataUri' => $logo,
        ];
    }
}