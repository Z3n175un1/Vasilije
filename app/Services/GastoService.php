<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CondicionPago;
use App\Enums\TipoGasto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio compartidas entre gastos de UNIDAD y gastos GENERALES.
 *
 * Concentra aqui lo que antes estaba duplicado (y por tanto, discrepante)
 * en GastoController y GastoGeneralController:
 *  - la logica de devolucion (monto negativo)
 *  - la validacion condicional banco/proveedor segun condicion de pago
 */
final class GastoService
{
    public function __construct(private readonly DocumentoService $documentos) {}

    /**
     * Un monto negativo significa DEVOLUCION: se registra tal cual pero
     * se marca explicitamente para que los reportes y la UI lo muestren.
     */
    public static function esDevolucion(float $monto): bool
    {
        return $monto < 0;
    }

    /**
     * Normaliza el payload de un gasto de unidad / general.
     *
     * @param  array  $datos
     * @return array
     */
    public function normalizar(array $datos): array
    {
        $condicion = CondicionPago::from($datos['condicion_pago'] ?? CondicionPago::Contado->value);
        $monto = round((float) $datos['monto'], 2);

        if ($monto === 0.0) {
            throw ValidationException::withMessages([
                'monto' => 'El monto no puede ser cero. Use un monto positivo para un gasto o uno negativo para una devolución.',
            ]);
        }

        // Regla de coherence: CONTADO se paga desde una cuenta, CREDITO genera
        // deuda con un proveedor.
        if ($condicion === CondicionPago::Contado && empty($datos['id_banco'])) {
            throw ValidationException::withMessages([
                'id_banco' => 'Para un pago CONTADO debe seleccionar la cuenta bancaria de salida.',
            ]);
        }

        if ($condicion === CondicionPago::Credito && empty($datos['id_proveedor'])) {
            throw ValidationException::withMessages([
                'id_proveedor' => 'Para un pago CRÉDITO debe seleccionar el proveedor.',
            ]);
        }

        return array_merge($datos, [
            'condicion_pago' => $condicion->value,
            'monto' => $monto,
            'es_devolucion' => self::esDevolucion($monto),
        ]);
    }

    /**
 * * Reglas de validacion compartidas para el gasto de UNIDAD.
 *
     * Las tablas NO se cualifican con el esquema (`global.vehiculos`): en las
     * reglas `exists:` / `unique:` Laravel interpreta el prefijo como nombre
     * de CONEXION y falla con "Database connection [global] not configured".
     * La conexion ya declara `search_path = global`.
     *
     * @return array<string, mixed>
     */
    public static function reglasUnidad(bool $esUpdate = false): array
    {
        return [
            'id_vehiculo' => ['required', 'integer', 'exists:vehiculos,id_vehiculo'],
            'id_clasificador' => ['nullable', 'integer'],
            'tipo_gasto' => ['nullable', 'string', 'in:' . implode(',', TipoGasto::values())],
            'concepto' => ['required', 'string', 'max:200'],
            'monto' => ['required', 'numeric'],
            'fecha_gasto' => ['required', 'date'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'id_proveedor' => ['nullable', 'integer', 'exists:proveedores,id_proveedor'],
            'id_banco' => ['nullable', 'integer', 'exists:bancos,id_banco'],
            'id_personal' => ['nullable', 'integer', 'exists:personal,id_personal'],
            'condicion_pago' => ['nullable', 'string', 'in:' . implode(',', CondicionPago::values())],
            'metodo_pago' => ['nullable', 'string', 'in:BANCO,CAJA_CHICA'],
            'fecha_limite_pago' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'kilometraje' => ['nullable', 'numeric', 'min:0'],
            'cantidad' => ['nullable', 'numeric', 'min:0'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            // Solo aplica a gastos de combustible
            'tipo_combustible' => ['nullable', 'string', 'max:50'],
            'litros' => ['nullable', 'numeric', 'min:0'],
            'precio_por_litro' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function reglasGeneral(): array
    {
        return [
            'id_clasificador' => ['required', 'integer'],
            'concepto' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric'],
            'fecha_gasto' => ['required', 'date'],
            'condicion_pago' => ['required', 'string', 'in:' . implode(',', CondicionPago::values())],
            'id_banco' => ['nullable', 'integer', 'exists:bancos,id_banco'],
            'id_proveedor' => ['nullable', 'integer', 'exists:proveedores,id_proveedor'],
            'nro_comprobante' => ['nullable', 'string', 'max:50'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Sincroniza (o crea) el detalle de combustible del gasto.
     */
    public function sincronizarCombustible(int $idGasto, ?string $tipo, ?float $litros, ?float $precio): void
    {
        if ($tipo === null && $litros === null && $precio === null) {
            DB::table('global.combustible_detalle')->where('id_gasto', $idGasto)->delete();

            return;
        }

        $payload = [
            'tipo_carburante' => $tipo ?: 'Diesel',
            'galones' => $litros ?? 0,
            'precio_por_galon' => $precio ?? 0,
        ];

        $existe = DB::table('global.combustible_detalle')->where('id_gasto', $idGasto)->exists();

        if ($existe) {
            DB::table('global.combustible_detalle')->where('id_gasto', $idGasto)->update($payload);
        } else {
            DB::table('global.combustible_detalle')->insert($payload + ['id_gasto' => $idGasto]);
        }
    }

    /**
     * El gasto y su detalle de combustible deben eliminarse juntos.
     */
    public function eliminarGastoUnidad(int $idGasto): void
    {
        DB::transaction(function () use ($idGasto): void {
            DB::table('global.combustible_detalle')->where('id_gasto', $idGasto)->delete();
            DB::table('global.gastos')->where('id_gasto', $idGasto)->delete();
        });
    }
}
