<?php

declare(strict_types=1);

namespace App\Http\Requests\Facturacion;

use App\Enums\EstadoFactura;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Flete (ingreso por transporte).
 *
 * `nro_documento` NO se acepta del cliente: lo genera
 * DocumentoService con la secuencia atomica de la serie I_.
 */
class StoreFleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('crear', 'facturacion');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'id_vehiculo' => ['required', 'integer', Rule::exists('vehiculos', 'id_vehiculo')],
            'id_personal' => ['nullable', 'integer', Rule::exists('personal', 'id_personal')],
            'id_tramo' => ['nullable', 'integer', Rule::exists('tramos', 'id_tramo')],
            'concepto' => ['nullable', 'string', 'max:200'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'tipo_cambio' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'fecha_ingreso' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_ingreso'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'cliente_nombre' => ['nullable', 'string', 'max:200'],
            'cliente_nit' => ['nullable', 'string', 'max:30'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'origen' => ['nullable', 'string', 'max:200'],
            'destino' => ['nullable', 'string', 'max:200'],
            'toneladas' => ['nullable', 'numeric', 'min:0'],
            'kilometraje_conducido' => ['nullable', 'numeric', 'min:0'],
            'tipo_pago' => ['nullable', 'string', 'max:30'],
            'estado_factura' => ['nullable', 'string', Rule::in(EstadoFactura::values())],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'monto.gt' => 'El monto del flete debe ser mayor a cero.',
            'id_vehiculo.exists' => 'La unidad seleccionada no existe.',
            'fecha_vencimiento.after_or_equal' => 'El vencimiento no puede ser anterior a la fecha del flete.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'id_vehiculo' => 'unidad',
            'id_personal' => 'conductor',
            'toneladas' => 'toneladas',
            'kilometraje_conducido' => 'kilometraje conducido',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function datosNormalizados(): array
    {
        // El formulario rellena origen, destino y kilometraje con JavaScript,
        // pero esos campos son `readonly` y no viajan si el navegador va lento
        // o bloquea el script. Si hay ruta elegida, la base manda: se toma del
        // tramo y no de lo que envio el formulario. Asi el flete nunca queda
        // con la ruta seleccionada a medio Poblar.
        $tramo = null;

        if ($this->filled('id_tramo')) {
            $tramo = \Illuminate\Support\Facades\DB::table('tramos')
                ->where('id_tramo', $this->integer('id_tramo'))
                ->first(['origen', 'destino', 'kilometros']);
        }

        return [
            'id_vehiculo' => $this->integer('id_vehiculo'),
            'id_personal' => $this->integer('id_personal') ?: null,
            'id_tramo' => $this->integer('id_tramo') ?: null,
            'concepto' => $this->filled('concepto')
                ? $this->string('concepto')->trim()->value()
                : 'TRANSPORTE DE SOYA',
            'monto' => round((float) $this->input('monto'), 2),
            // El tipo de cambio con el que se factura. Si el formulario no
            // lo manda, el controlador usa el vigente de configuracion.
            'tipo_cambio' => $this->filled('tipo_cambio')
                ? round((float) $this->input('tipo_cambio'), 2)
                : null,
            'fecha_ingreso' => $this->date('fecha_ingreso')->format('Y-m-d'),
            'fecha_vencimiento' => $this->input('fecha_vencimiento'),
            'observaciones' => $this->input('observaciones'),
            'cliente_nombre' => $this->input('cliente_nombre'),
            'cliente_nit' => $this->input('cliente_nit'),
            'cliente_telefono' => $this->input('cliente_telefono'),
            'origen' => $tramo?->origen ?? $this->input('origen'),
            'destino' => $tramo?->destino ?? $this->input('destino'),
            'toneladas' => $this->input('toneladas') ?? 0,
            'kilometraje_conducido' => $tramo?->kilometros ?? $this->input('kilometraje_conducido') ?? 0,
            'tipo_pago' => $this->input('tipo_pago', 'EFECTIVO'),
            'estado_factura' => $this->input('estado_factura', EstadoFactura::Pendiente->value),
        ];
    }
}