<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TipoMovimiento;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fuente unica de verdad del inventario: stock, lotes y costo promedio.
 *
 * TODO el movimiento de stock ocurre DENTRO de una transaccion. El codigo
 * anterior hacia "revertir el delta viejo, luego aplicar el nuevo" sin
 * transaccion: si fallaba el paso 3, el stock quedaba corrupto de forma
 * silenciosa y permanente.
 */
final class InventarioService
{
    public function __construct(private readonly DocumentoService $documentos) {}

    /**
     * Registra un movimiento de inventario aplicando sus efectos sobre
     * `inventario` y, si aplica, sobre `lotes`.
     *
     * @param  array{id_inventario:int, tipo_movimiento:string, cantidad:string|float,
     *               fecha_movimiento?:string, precio_unitario?:?float, id_lote?:?int,
     *               codigo_lote?:?string, id_vehiculo?:?int, id_personal?:?int,
     *               id_proveedor?:?int, id_banco?:?int, condicion_pago?:?string,
     *               metodo_pago?:?string, fecha_limite_pago?:?string,
     *               proveedor?:?string, numero_documento?:?string,
     *               observaciones?:?string, registrado_por?:?int}  $datos
     * @return int  id_movimiento generado
     */
    public function registrarMovimiento(array $datos): int
    {
        $tipo = TipoMovimiento::from($datos['tipo_movimiento']);
        $cantidad = round((float) $datos['cantidad'], 2);

        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }

        $precioUnitario = isset($datos['precio_unitario']) && $datos['precio_unitario'] !== ''
            ? round((float) $datos['precio_unitario'], 2)
            : null;

        return DB::transaction(function () use ($datos, $tipo, $cantidad, $precioUnitario): int {
            $inventario = DB::table('global.inventario')
                ->where('id_inventario', $datos['id_inventario'])
                ->lockForUpdate()
                ->first();

            if (!$inventario) {
                throw new RuntimeException('El producto no existe en el inventario.');
            }

            if ($tipo->restaStock()) {
                $disponible = (float) ($inventario->stock_actual ?? 0);
                if ($cantidad > $disponible) {
                    throw new RuntimeException(sprintf(
                        'Stock insuficiente para "%s": disponible %s, solicitado %s.',
                        $inventario->nombre_producto,
                        number_format($disponible, 2),
                        number_format($cantidad, 2),
                    ));
                }
            }

            $idLote = $datos['id_lote'] ?? null;
            $codigoLote = $datos['codigo_lote'] ?? null;

            if ($tipo->generaLote() && !empty($codigoLote)) {
                $idLote = $this->aplicarCompraEnLote(
                    (int) $datos['id_inventario'],
                    (string) $codigoLote,
                    $cantidad,
                    $datos['precio_compra'] ?? null,
                    $datos['fecha_movimiento'] ?? date('Y-m-d'),
                );
            }

            $idMovimiento = DB::table('global.movimientos_inventario')->insertGetId([
                'id_inventario' => $datos['id_inventario'],
                'id_lote' => $idLote,
                'tipo_movimiento' => $tipo->value,
                'cantidad' => $cantidad,
                'costo_unitario' => $precioUnitario,
                // `costo_total` es columna generada (cantidad * costo_unitario),
                // asi que PostgreSQL la calcula: incluirla en el INSERT da
                // error "no se puede insertar un valor no-predeterminado".
                'id_vehiculo' => $datos['id_vehiculo'] ?? null,
                'id_personal' => $datos['id_personal'] ?? null,
                'id_proveedor' => $datos['id_proveedor'] ?? null,
                'id_banco' => $datos['id_banco'] ?? null,
                'documento_numero' => $datos['numero_documento'] ?? null,
                'proveedor' => $datos['proveedor'] ?? null,
                'fecha_movimiento' => $datos['fecha_movimiento'] ?? date('Y-m-d'),
                'motivo' => $datos['motivo'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'condicion_pago' => $datos['condicion_pago'] ?? 'CONTADO',
                'metodo_pago' => $datos['metodo_pago'] ?? null,
                'fecha_limite_pago' => $datos['fecha_limite_pago'] ?? null,
                // `getKey()` y no `auth()->id()`: como la columna de identificacion
                // es `usuario`, `auth()->id()` devuelve el nombre de login
                // (string) y `registrado_por` es un integer.
                'registrado_por' => $datos['registrado_por'] ?? auth()->user()?->getKey(),
                'created_at' => now(),
            ], 'id_movimiento');

            $this->aplicarStock((int) $datos['id_inventario'], $tipo, $cantidad, $precioUnitario);

            return $idMovimiento;
        });
    }

    /**
     * Edita un movimiento revirtiendo sus efectos y aplicando los nuevos,
     * todo dentro de la misma transaccion.
     *
     * @param  array  $datos  misma forma que registrarMovimiento()
     */
    public function actualizarMovimiento(int $idMovimiento, array $datos): void
    {
        $tipo = TipoMovimiento::from($datos['tipo_movimiento']);
        $cantidad = round((float) $datos['cantidad'], 2);

        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }

        $precioUnitario = isset($datos['precio_unitario']) && $datos['precio_unitario'] !== ''
            ? round((float) $datos['precio_unitario'], 2)
            : null;

        DB::transaction(function () use ($idMovimiento, $datos, $tipo, $cantidad, $precioUnitario): void {
            $movimiento = DB::table('global.movimientos_inventario')
                ->where('id_movimiento', $idMovimiento)
                ->lockForUpdate()
                ->first();

            if (!$movimiento) {
                throw new RuntimeException('Movimiento no encontrado.');
            }

            $tipoViejo = TipoMovimiento::from($movimiento->tipo_movimiento);

            // 1. Revertir efectos del movimiento anterior.
            $this->revertirStock((int) $movimiento->id_inventario, $tipoViejo, (float) $movimiento->cantidad);

            if ($tipoViejo->generaLote() && !empty($movimiento->id_lote)) {
                DB::table('global.lotes')
                    ->where('id_lote', $movimiento->id_lote)
                    ->decrement('cantidad_actual', (float) $movimiento->cantidad);
            }

            // 2. Validar disponibilidad con el stock ya revertido.
            if ($tipo->restaStock()) {
                $disponible = (float) DB::table('global.inventario')
                    ->where('id_inventario', $datos['id_inventario'])
                    ->value('stock_actual');
                if ($cantidad > $disponible) {
                    throw new RuntimeException(sprintf(
                        'Stock insuficiente: disponible %s, solicitado %s.',
                        number_format($disponible, 2),
                        number_format($cantidad, 2),
                    ));
                }
            }

            // 3. Aplicar lote si la compra mantiene/crea codigo de lote.
            $idLote = $movimiento->id_lote;
            $codigoLote = $datos['codigo_lote'] ?? null;

            if ($tipo->generaLote() && !empty($codigoLote)) {
                $nuevoLoteId = $this->obtenerOCrearLote(
                    (int) $datos['id_inventario'],
                    (string) $codigoLote,
                    $datos['fecha_movimiento'] ?? date('Y-m-d'),
                    $datos['precio_compra'] ?? null,
                );
                if ($nuevoLoteId !== (int) $movimiento->id_lote) {
                    // Si cambio el codigo de lote, corregimos el lote anterior.
                    DB::table('global.lotes')
                        ->where('id_lote', $movimiento->id_lote)
                        ->update(['cantidad_actual' => DB::raw('GREATEST(COALESCE(cantidad_actual,0) - ' . (float) $movimiento->cantidad . ', 0)')]);
                }
                $idLote = $nuevoLoteId;
            }

            // 4. Actualizar el movimiento.
            DB::table('global.movimientos_inventario')
                ->where('id_movimiento', $idMovimiento)
                ->update([
                    'id_inventario' => $datos['id_inventario'],
                    'id_lote' => $idLote,
                    'tipo_movimiento' => $tipo->value,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $precioUnitario,
                    // Columna generada: ver la nota en el INSERT.
                    'id_vehiculo' => $datos['id_vehiculo'] ?? null,
                    'id_personal' => $datos['id_personal'] ?? null,
                    'id_proveedor' => $datos['id_proveedor'] ?? null,
                    'id_banco' => $datos['id_banco'] ?? null,
                    'documento_numero' => $datos['numero_documento'] ?? null,
                    'proveedor' => $datos['proveedor'] ?? null,
                    'fecha_movimiento' => $datos['fecha_movimiento'] ?? date('Y-m-d'),
                    'observaciones' => $datos['observaciones'] ?? null,
                    'condicion_pago' => $datos['condicion_pago'] ?? 'CONTADO',
                    'metodo_pago' => $datos['metodo_pago'] ?? null,
                    'fecha_limite_pago' => $datos['fecha_limite_pago'] ?? null,
                ]);

            // 5. Aplicar los efectos nuevos.
            if ($tipo->generaLote() && !empty($codigoLote)) {
                DB::table('global.lotes')->where('id_lote', $idLote)->increment('cantidad_actual', $cantidad);
            }

            $this->aplicarStock((int) $datos['id_inventario'], $tipo, $cantidad, $precioUnitario);
        });
    }

    /**
     * Elimina un movimiento revirtiendo su efecto sobre el stock.
     */
    public function eliminarMovimiento(int $idMovimiento): void
    {
        DB::transaction(function () use ($idMovimiento): void {
            $movimiento = DB::table('global.movimientos_inventario')
                ->where('id_movimiento', $idMovimiento)
                ->lockForUpdate()
                ->first();

            if (!$movimiento) {
                return;
            }

            $tipo = TipoMovimiento::from($movimiento->tipo_movimiento);
            $this->revertirStock((int) $movimiento->id_inventario, $tipo, (float) $movimiento->cantidad);

            if ($movimiento->id_lote) {
                DB::table('global.lotes')
                    ->where('id_lote', $movimiento->id_lote)
                    ->update(['cantidad_actual' => DB::raw('GREATEST(COALESCE(cantidad_actual,0) - ' . (float) $movimiento->cantidad . ', 0)')]);
            }

            DB::table('global.movimientos_inventario')->where('id_movimiento', $idMovimiento)->delete();
        });
    }

    /**
     * Sugiere el siguiente codigo de lote con formato LO-000000.
     */
    public function siguienteCodigoLote(): string
    {
        $ultimo = DB::table('global.lotes')
            ->selectRaw("COALESCE(MAX(NULLIF(regexp_replace(codigo_lote, '\\D', '', 'g'), '')::bigint), 0) AS m")
            ->value('m');

        return 'LO-' . str_pad((string) ((int) $ultimo + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Genera el codigo de producto PREFIJO-###-##### a partir del grupo.
     */
    public function generarCodigoProducto(?string $nombreGrupo, ?int $idCategoria): string
    {
        $prefijo = $nombreGrupo ? strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $nombreGrupo), 0, 2)) : 'XX';
        $prefijo = $prefijo !== '' ? $prefijo : 'XX';

        $item = (int) DB::table('global.inventario')
            ->where('id_categoria', $idCategoria)
            ->count() + 1;

        $ultimo = DB::table('global.inventario')
            ->where('codigo', 'like', $prefijo . '-%')
            ->orderByDesc('id_inventario')
            ->value('codigo');

        $seq = 0;
        if ($ultimo) {
            $partes = explode('-', $ultimo);
            if (count($partes) === 3 && is_numeric($partes[2])) {
                $seq = (int) $partes[2];
            }
        }

        return sprintf('%s-%03d-%05d', $prefijo, $item, $seq + 1);
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    private function aplicarStock(int $idInventario, TipoMovimiento $tipo, float $cantidad, ?float $precio): void
    {
        $columnas = ['updated_at' => now()];

        if ($tipo->sumaStock()) {
            $columnas['stock_actual'] = DB::raw('COALESCE(stock_actual, 0) + ' . $cantidad);
            $columnas['fecha_ultima_compra'] = DB::raw('CURRENT_DATE');
            if ($precio !== null) {
                $columnas['precio_compra'] = $precio;
                $columnas['ultimo_costo'] = $precio;
            }
        } else {
            $columnas['stock_actual'] = DB::raw('GREATEST(COALESCE(stock_actual, 0) - ' . $cantidad . ', 0)');
            $columnas['fecha_ultima_salida'] = DB::raw('CURRENT_DATE');
        }

        DB::table('global.inventario')->where('id_inventario', $idInventario)->update($columnas);
    }

    private function revertirStock(int $idInventario, TipoMovimiento $tipo, float $cantidad): void
    {
        if ($tipo->sumaStock()) {
            DB::table('global.inventario')
                ->where('id_inventario', $idInventario)
                ->update(['stock_actual' => DB::raw('GREATEST(COALESCE(stock_actual, 0) - ' . $cantidad . ', 0)')]);
        } else {
            DB::table('global.inventario')
                ->where('id_inventario', $idInventario)
                ->update(['stock_actual' => DB::raw('COALESCE(stock_actual, 0) + ' . $cantidad)]);
        }
    }

    private function obtenerOCrearLote(int $idInventario, string $codigo, string $fecha, mixed $precioCompra, float $cantidad = 0): int
    {
        $existente = DB::table('global.lotes')
            ->where('codigo_lote', $codigo)
            ->where('id_inventario', $idInventario)
            ->value('id_lote');

        if ($existente) {
            return (int) $existente;
        }

        return DB::table('global.lotes')->insertGetId([
            'id_inventario' => $idInventario,
            'codigo_lote' => $codigo,
            'fecha_ingreso' => $fecha,
            'cantidad_inicial' => $cantidad,
            'cantidad_actual' => 0,
            'precio_compra' => $precioCompra ?? 0,
            'estado' => 'ACTIVO',
            'created_at' => now(),
        ], 'id_lote');
    }

    /**
     * Devuelve el id del lote, creandolo si hace falta. La cantidad NO se
     * suma aqui: la suma la hace el llamador de forma explicita.
     */
    private function aplicarCompraEnLote(int $idInventario, string $codigo, float $cantidad, mixed $precioCompra, string $fecha): int
    {
        $existente = DB::table('global.lotes')
            ->where('codigo_lote', $codigo)
            ->where('id_inventario', $idInventario)
            ->first();

        if ($existente) {
            $loteId = (int) $existente->id_lote;
            $columnas = [
                'cantidad_actual' => DB::raw('COALESCE(cantidad_actual,0) + ' . $cantidad),
            ];
            if ($precioCompra !== null && $precioCompra !== '') {
                $columnas['precio_compra'] = (float) $precioCompra;
            }
            DB::table('global.lotes')->where('id_lote', $loteId)->update($columnas);

            return $loteId;
        }

        return DB::table('global.lotes')->insertGetId([
            'id_inventario' => $idInventario,
            'codigo_lote' => $codigo,
            'fecha_ingreso' => $fecha,
            'cantidad_inicial' => $cantidad,
            'cantidad_actual' => $cantidad,
            'precio_compra' => $precioCompra ?? 0,
            'estado' => 'ACTIVO',
            'created_at' => now(),
        ], 'id_lote');
    }
}
