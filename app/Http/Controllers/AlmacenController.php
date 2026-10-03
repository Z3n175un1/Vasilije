<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoVehiculo;
use App\Enums\TipoMovimiento;
use App\Http\Requests\Almacen\StoreMovimientoRequest;
use App\Services\AuditoriaService;
use App\Services\InventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * ALMACEN: productos, movimientos, lotes y kardex.
 *
 * CAMBIOS RESPECTO A LA VERSION ANTERIOR
 * --------------------------------------
 *  1. Todo movimiento de stock pasa por InventarioService, que lo ejecuta
 *     DENTRO de una transaccion. Antes el controlador hacia "revertir el
 *     delta viejo y aplicar el nuevo" sin transaccion: un fallo a mitad
 *     dejaba el stock corrupto de forma permanente y silenciosa.
 *  2. Se valida stock disponible antes de una salida: antes se permitia
 *     dejar el stock en negativo con GREATEST(0,...) silencioso, y en la
 *     version previa sin GREATEST, directamente en negativo.
 *  3. `costo_total` se calcula siempre (la columna existia pero nunca se
 *     escribia, y el estado de cuenta la lee).
 *  4. Se elimina la logica duplicada de generateNextCode, que ahora vive
 *     en InventarioService.
 */
class AlmacenController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventario,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(): View
    {
        return view('almacen.index', [
            'bancos' => DB::table('global.bancos')->where('estado', 'ACTIVO')->orderBy('nombre_banco')->get(),
            'proveedores' => DB::table('global.proveedores')->where('estado', 1)->orderBy('nombre_proveedor')->get(),
        ]);
    }

    public function create(): View
    {
        return view('almacen.form', [
            'producto' => null,
            'categorias' => DB::table('global.categorias_almacen')->orderBy('nombre')->get(),
            'proveedores' => DB::table('global.proveedores')->where('estado', 1)->orderBy('nombre_proveedor')->get(),
        ]);
    }

    /**
     * Formulario de compras y entregas en su propia pagina.
     *
     * Antes esto vivia en un modal dentro del listado. El modal obligaba a
     * mantener el listado cargado en memoria y hacia el formulario(endpoint
     * REST dentro de un HTML de tabla, donde un fallo dejaba al operador sin
     * forma de recuperar el estado. Ahora es una ruta navegable: permite
     * recargar, compartir el enlace y volver con el panel de carga entre
     * paginas.
     *
     * @param  string  $tipo  COMPRA o ENTREGA
     */
    public function movimientoForm(string $tipo, ?string $id = null): View|RedirectResponse
    {
        // La UI habla de COMPRA/ENTREGA y son exactamente los valores que
        // guarda el inventario: COMPRA suma stock, SALIDA lo resta.
        $interno = match (strtoupper($tipo)) {
            'ENTREGA' => TipoMovimiento::Salida->value,
            'COMPRA' => TipoMovimiento::Compra->value,
            default => null,
        };

        if ($interno === null) {
            return redirect()->route('almacen.index')
                ->with('error', 'Tipo de movimiento no válido.');
        }

        $esEdicion = $id !== null;

        if ($esEdicion && !auth()->user()?->can('editar', 'inventario')) {
            return redirect()->route('almacen.index')
                ->with('error', 'Su rol no permite modificar movimientos.');
        }

        $movimiento = null;

        if ($esEdicion) {
            $movimiento = DB::table('global.movimientos_inventario')
                ->where('id_movimiento', $id)
                ->first();

            if (!$movimiento) {
                return redirect()->route('almacen.index')
                    ->with('error', 'Movimiento no encontrado.');
            }

            // Editar un movimiento del tipo contrario al del formulario
            // Llevaria a guardar sobre el tipo equivocado.
            if ($movimiento->tipo_movimiento !== $interno) {
                return redirect()->route('almacen.index')
                    ->with('error', 'El movimiento no corresponde a ' . strtoupper($tipo) . '.');
            }
        }

        return view('almacen.form-alm', [
            'tipoMovimiento' => $interno,
            'tipoEtiqueta' => strtoupper($tipo),
            // La vista usa esta bandera para mostrar solo los campos que
            // aplican: las entregas no tienen precio ni condición de pago.
            'esEntrega' => $interno === TipoMovimiento::Salida->value,
            'movimiento' => $movimiento,
            'bancos' => DB::table('global.bancos')->where('estado', 'ACTIVO')->orderBy('nombre_banco')->get(),
            'proveedores' => DB::table('global.proveedores')->where('estado', 1)->orderBy('nombre_proveedor')->get(),

            // El catálogo se pasa a la vista para que el buscador de producto
            // tenga con qué filtrar y para poder mostrar el stock antes de
            // guardar. Antes se pedía por fetch después de cargar, lo que
            // dejaba el formulario medio segundo sin opciones.
            'productos' => DB::table('global.inventario')
                ->select(
                    'id_inventario',
                    'codigo',
                    'codigo_barras',
                    'nombre_producto',
                    'unidad_medida',
                    'stock_actual',
                    'stock_minimo',
                )
                ->orderBy('nombre_producto')
                ->get(),
        ]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        if (!auth()->user()?->can('editar', 'inventario')) {
            return redirect()->route('almacen.index')
                ->with('error', 'Su rol no permite modificar productos.');
        }

        $producto = DB::table('global.inventario')->where('id_inventario', $id)->first();

        if (!$producto) {
            return redirect()->route('almacen.index')->with('error', 'Producto no encontrado.');
        }

        return view('almacen.form', [
            'producto' => $producto,
            'categorias' => DB::table('global.categorias_almacen')->orderBy('nombre')->get(),
            'proveedores' => DB::table('global.proveedores')->where('estado', 1)->orderBy('nombre_proveedor')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:20', 'unique:inventario,codigo'],
            'nombre_producto' => ['required', 'string', 'max:100'],
            'id_categoria' => ['nullable', 'integer', 'exists:categorias_almacen,id_categoria'],
            'unidad_medida' => ['required', 'string', 'max:20'],
            'stock_actual' => ['nullable', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'precio_compra' => ['nullable', 'numeric', 'min:0'],
            'id_proveedor' => ['nullable', 'integer', 'exists:proveedores,id_proveedor'],
            'marca' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'codigo_barras' => ['nullable', 'string', 'max:50'],
        ]);

        $data['estado'] = 'ACTIVO';
        $data['stock_actual'] ??= 0;
        $data['stock_minimo'] ??= 0;
        $data['precio_compra'] ??= 0;
        $data['created_by'] = auth()->user()?->getKey();

        if (empty($data['codigo'])) {
            $data['codigo'] = $this->inventario->generarCodigoProducto(
                $this->nombreGrupo($data['id_categoria'] ?? null),
                isset($data['id_categoria']) ? (int) $data['id_categoria'] : null,
            );
        }

        $id = DB::table('global.inventario')->insertGetId($data, 'id_inventario');

        $this->auditoria->registrar('PRODUCTO_CREADO', 'inventario', "Producto {$data['codigo']} creado", ['id' => $id]);

        return redirect()->route('almacen.index')->with('success', 'Producto registrado exitosamente');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        if (!auth()->user()?->can('editar', 'inventario')) {
            return redirect()->route('almacen.index')
                ->with('error', 'Su rol no permite modificar productos.');
        }

        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:20', 'unique:inventario,codigo,' . $id . ',id_inventario'],
            'nombre_producto' => ['required', 'string', 'max:100'],
            'id_categoria' => ['nullable', 'integer', 'exists:categorias_almacen,id_categoria'],
            'unidad_medida' => ['required', 'string', 'max:20'],
            // El stock NO se edita a mano: es consecuencia de los movimientos.
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'precio_compra' => ['nullable', 'numeric', 'min:0'],
            'id_proveedor' => ['nullable', 'integer', 'exists:proveedores,id_proveedor'],
            'marca' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'codigo_barras' => ['nullable', 'string', 'max:50'],
        ]);

        unset($data['stock_actual']);

        $data['updated_at'] = now();

        DB::table('global.inventario')->where('id_inventario', $id)->update($data);

        $this->auditoria->registrar('PRODUCTO_ACTUALIZADO', 'inventario', "Producto #{$id} actualizado", ['id' => $id]);

        return redirect()->route('almacen.index')->with('success', 'Producto actualizado exitosamente');
    }

    public function destroy(string $id): RedirectResponse
    {
        // Baja logica: el producto puede tener movimientos historicos.
        DB::table('global.inventario')
            ->where('id_inventario', $id)
            ->update(['estado' => 'INACTIVO', 'updated_at' => now()]);

        $this->auditoria->registrar('PRODUCTO_DESACTIVADO', 'inventario', "Producto #{$id} desactivado", ['id' => $id]);

        return redirect()->route('almacen.index')->with('success', 'Producto desactivado');
    }

    // ------------------------------------------------------------------
    // API
    // ------------------------------------------------------------------

    public function apiList(Request $request): JsonResponse
    {
        $query = DB::table('global.inventario')
            ->leftJoin('global.proveedores', 'global.inventario.id_proveedor', '=', 'global.proveedores.id_proveedor')
            ->leftJoin('global.categorias_almacen', 'global.inventario.id_categoria', '=', 'global.categorias_almacen.id_categoria')
            ->select(
                'global.inventario.*',
                'global.proveedores.nombre_proveedor',
                'global.categorias_almacen.nombre as categoria',
                DB::raw('(SELECT codigo_lote FROM global.lotes
                          WHERE id_inventario = global.inventario.id_inventario
                          ORDER BY id_lote DESC LIMIT 1) as codigo_lote'),
                DB::raw('(SELECT costo_unitario FROM global.movimientos_inventario
                          WHERE id_inventario = global.inventario.id_inventario
                            AND tipo_movimiento = \'COMPRA\'
                          ORDER BY id_movimiento DESC LIMIT 1) as ultimo_precio'),
            )
            ->where('global.inventario.estado', 'ACTIVO');

        if ($request->filled('categoria')) {
            $categoriaId = DB::table('global.categorias_almacen')
                ->where('nombre', $request->string('categoria')->value())
                ->value('id_categoria');

            if ($categoriaId) {
                $query->where('global.inventario.id_categoria', $categoriaId);
            }
        }

        if ($request->filled('busqueda')) {
            $busqueda = $request->string('busqueda')->value();

            $query->where(function ($q) use ($busqueda) {
                $q->where('global.inventario.nombre_producto', 'ilike', "%{$busqueda}%")
                    ->orWhere('global.inventario.codigo', 'ilike', "%{$busqueda}%")
                    ->orWhere('global.inventario.codigo_barras', 'ilike', "%{$busqueda}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('global.inventario.nombre_producto')->get(),
        ]);
    }

    public function apiShow(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.inventario')->where('id_inventario', $id)->first(),
        ]);
    }

    public function apiCategorias(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('global.categorias_almacen')->orderBy('nombre')->get(),
        ]);
    }

    public function apiMovimientos(Request $request): JsonResponse
    {
        $query = DB::table('global.movimientos_inventario')
            ->leftJoin('global.inventario', 'global.movimientos_inventario.id_inventario', '=', 'global.inventario.id_inventario')
            ->leftJoin('global.vehiculos', 'global.movimientos_inventario.id_vehiculo', '=', 'global.vehiculos.id_vehiculo')
            ->leftJoin('global.personal', 'global.movimientos_inventario.id_personal', '=', 'global.personal.id_personal')
            ->leftJoin('global.lotes', 'global.movimientos_inventario.id_lote', '=', 'global.lotes.id_lote')
            ->select(
                'global.movimientos_inventario.*',
                'global.inventario.nombre_producto',
                'global.inventario.codigo',
                'global.inventario.codigo_barras',
                'global.vehiculos.placa_vehiculo',
                DB::raw("COALESCE(CONCAT(global.personal.nombres, ' ', global.personal.apellidos), '') as conductor"),
                DB::raw('COALESCE(global.lotes.codigo_lote, \'\') as codigo_lote'),
            )
            ->orderByDesc('global.movimientos_inventario.fecha_movimiento');

        if ($request->filled('id_inventario')) {
            $query->where('global.movimientos_inventario.id_inventario', $request->integer('id_inventario'));
        }

        if ($request->filled('tipo')) {
            $query->where('global.movimientos_inventario.tipo_movimiento', $request->string('tipo')->value());
        }

        if ($request->filled('id_movimiento')) {
            $query->where('global.movimientos_inventario.id_movimiento', $request->integer('id_movimiento'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit((int) min($request->integer('limit', 100), 500))->get(),
        ]);
    }

    /**
     * Registra una compra o una entrega. Todo el trabajo de stock ocurre en
     * InventarioService, dentro de una transaccion.
     */
    public function apiGuardarMovimiento(StoreMovimientoRequest $request): JsonResponse
    {
        try {
            $datos = $request->datosNormalizados();

            if (empty($datos['codigo_lote']) && $datos['tipo_movimiento'] === 'COMPRA') {
                $datos['codigo_lote'] = $this->inventario->siguienteCodigoLote();
            }

            $id = $this->inventario->registrarMovimiento($datos);

            $this->auditoria->registrar(
                'MOVIMIENTO_REGISTRADO',
                'inventario',
                sprintf('%s de %.2f', $datos['tipo_movimiento'], $datos['cantidad']),
                ['id' => $id, 'producto' => $datos['id_inventario']]
            );

            return response()->json(['success' => true, 'message' => 'Movimiento registrado', 'id_movimiento' => $id]);
        } catch (Throwable $e) {
            // Los mensajes de dominio (stock insuficiente, etc.) son utiles
            // al usuario; los internos no se exponen.
            $esNegocio = $e instanceof \RuntimeException;

            report($e);

            return response()->json([
                'success' => false,
                'message' => $esNegocio ? $e->getMessage() : 'No se pudo registrar el movimiento.',
            ], $esNegocio ? 422 : 500);
        }
    }

    public function apiActualizarMovimiento(StoreMovimientoRequest $request, string $id): JsonResponse
    {
        try {
            $this->inventario->actualizarMovimiento((int) $id, $request->datosNormalizados());

            $this->auditoria->registrar('MOVIMIENTO_ACTUALIZADO', 'inventario', "Movimiento #{$id} actualizado", ['id' => $id]);

            return response()->json(['success' => true, 'message' => 'Movimiento actualizado']);
        } catch (Throwable $e) {
            $esNegocio = $e instanceof \RuntimeException;

            report($e);

            return response()->json([
                'success' => false,
                'message' => $esNegocio ? $e->getMessage() : 'No se pudo actualizar el movimiento.',
            ], $esNegocio ? 422 : 500);
        }
    }

    public function apiEliminarMovimiento(Request $request, string $id): JsonResponse
    {
        if (!$request->user()?->can('eliminar', 'inventario')) {
            return response()->json(['success' => false, 'message' => 'Sin permisos para eliminar movimientos.'], 403);
        }

        try {
            $this->inventario->eliminarMovimiento((int) $id);

            $this->auditoria->registrar('MOVIMIENTO_ELIMINADO', 'inventario', "Movimiento #{$id} eliminado", ['id' => $id]);

            return response()->json(['success' => true, 'message' => 'Movimiento eliminado']);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'No se pudo eliminar el movimiento.'], 500);
        }
    }

    public function apiUltimoLote(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['codigo_lote' => $this->inventario->siguienteCodigoLote()],
        ]);
    }

    public function apiNextCode(Request $request): JsonResponse
    {
        $idCategoria = $request->integer('id_categoria');

        if (!$idCategoria) {
            return response()->json(['success' => false, 'message' => 'Seleccione un grupo.'], 422);
        }

        $nombre = $this->nombreGrupo($idCategoria);

        if ($nombre === null) {
            return response()->json(['success' => false, 'code' => ''], 422);
        }

        return response()->json([
            'success' => true,
            'code' => $this->inventario->generarCodigoProducto($nombre, $idCategoria),
        ]);
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

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