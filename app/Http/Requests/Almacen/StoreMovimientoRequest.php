<?php

declare(strict_types=1);

namespace App\Http\Requests\Almacen;

use App\Enums\CondicionPago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Movimiento de inventario (compra o entrega).
 *
 * El `tipo_movimiento` se limita a los valores que la aplicacion produce.
 * El CHECK de la base admite tambien INGRESO/CONSUMO/ENTRADA por
 * compatibilidad con datos historicos, pero la UI no debe ofrecerlos.
 */
class StoreMovimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('crear', 'inventario');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $esCompra = $this->input('tipo_movimiento') === 'COMPRA';

        $reglas = [
            'id_inventario' => ['required', 'integer', Rule::exists('inventario', 'id_inventario')],
            'tipo_movimiento' => ['required', 'string', Rule::in(['COMPRA', 'SALIDA'])],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'fecha_movimiento' => ['nullable', 'date'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'codigo_lote' => ['nullable', 'string', 'max:50'],
            'id_vehiculo' => ['nullable', 'integer', Rule::exists('vehiculos', 'id_vehiculo')],
            'id_personal' => ['nullable', 'integer', Rule::exists('personal', 'id_personal')],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'numero_documento' => ['nullable', 'string', 'max:50'],
        ];

        if ($esCompra) {
            $reglas += [
                'precio_compra' => ['nullable', 'numeric', 'min:0'],
                'id_proveedor' => ['nullable', 'integer', Rule::exists('proveedores', 'id_proveedor')],
                'id_banco' => ['nullable', 'integer', Rule::exists('bancos', 'id_banco')],
                'condicion_pago' => ['nullable', 'string', Rule::in(CondicionPago::values())],
                'metodo_pago' => ['nullable', 'string', Rule::in(['BANCO', 'CAJA_CHICA'])],
                'fecha_limite_pago' => ['nullable', 'date'],
            ];
        }

        return $reglas;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'cantidad.gt' => 'La cantidad debe ser mayor a cero.',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser COMPRA o SALIDA.',
            'id_inventario.exists' => 'El producto no existe en el inventario.',
            'id_vehiculo.exists' => 'La unidad seleccionada no existe.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'id_inventario' => 'producto',
            'precio_unitario' => 'precio unitario',
            'codigo_lote' => 'codigo de lote',
            'id_banco' => 'cuenta bancaria',
        ];
    }

    /**
     * Normaliza y resuelve las reglas de pago de la compra.
     *
     * @return array<string, mixed>
     */
    public function datosNormalizados(): array
    {
        $esCompra = $this->input('tipo_movimiento') === 'COMPRA';
        $condicion = $this->input('condicion_pago', CondicionPago::Contado->value);

        if ($esCompra && $condicion === CondicionPago::Contado->value && !$this->filled('id_banco')) {
            $this->validator?->errors()->add(
                'id_banco',
                'Para una compra CONTADA debe seleccionar la cuenta bancaria de salida.',
            );
        }

        if ($esCompra && $condicion === CondicionPago::Credito->value && !$this->filled('id_proveedor')) {
            $this->validator?->errors()->add(
                'id_proveedor',
                'Para una compra a CREDITO debe seleccionar el proveedor.',
            );
        }

        $nombreProveedor = null;
        if ($this->filled('id_proveedor')) {
            $nombreProveedor = \Illuminate\Support\Facades\DB::table('global.proveedores')
                ->where('id_proveedor', $this->integer('id_proveedor'))
                ->value('nombre_proveedor');
        }

        return [
            'id_inventario' => $this->integer('id_inventario'),
            'tipo_movimiento' => $this->string('tipo_movimiento')->value(),
            'cantidad' => round((float) $this->input('cantidad'), 2),
            'fecha_movimiento' => $this->input('fecha_movimiento') ?: date('Y-m-d'),
            'precio_unitario' => $this->filled('precio_unitario') ? (float) $this->input('precio_unitario') : null,
            'precio_compra' => $this->filled('precio_compra') ? (float) $this->input('precio_compra') : null,
            'codigo_lote' => $esCompra ? $this->input('codigo_lote') : null,
            'id_vehiculo' => $this->integer('id_vehiculo') ?: null,
            'id_personal' => $this->integer('id_personal') ?: null,
            'id_proveedor' => $this->integer('id_proveedor') ?: null,
            'id_banco' => $this->integer('id_banco') ?: null,
            'proveedor' => $nombreProveedor,
            'condicion_pago' => $esCompra ? $condicion : null,
            'metodo_pago' => $this->input('metodo_pago'),
            'fecha_limite_pago' => $this->input('fecha_limite_pago'),
            'observaciones' => $this->input('observaciones'),
            'numero_documento' => $this->input('numero_documento'),
        ];
    }
}