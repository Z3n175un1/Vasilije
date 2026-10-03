<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EstadoVehiculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Unidad de la flota.
 *
 * Nota: `anho`, `kilometraje` y `capacidad` son numeric en la BD pero el
 * formulario los offers como number; se normalizan aqui para no arrastrar
 * cadenas vacias.
 */
class StoreVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('editar', 'vehiculos');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'placa_vehiculo' => [
                'required', 'string', 'max:20',
                Rule::unique('vehiculos', 'placa_vehiculo')->ignore($id, 'id_vehiculo'),
            ],
            'tipo_vehiculo' => ['required', 'string', 'max:50'],
            'marca' => ['nullable', 'string', 'max:50'],
            'modelo' => ['nullable', 'string', 'max:50'],
            'anho' => ['nullable', 'integer', 'between:1980,' . (date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:30'],
            'capacidad' => ['nullable', 'numeric', 'min:0'],
            'tara_kg' => ['nullable', 'numeric', 'min:0'],
            'peso_bruto_kg' => ['nullable', 'numeric', 'min:0'],
            'peso_neto_kg' => ['nullable', 'numeric', 'min:0'],
            'kilometraje' => ['nullable', 'numeric', 'min:0'],
            'id_personal' => ['nullable', 'integer', Rule::exists('personal', 'id_personal')],
            'tramo_actual' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'estado' => ['required', 'integer', Rule::in(EstadoVehiculo::values())],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'placa_vehiculo.required' => 'La placa es obligatoria.',
            'placa_vehiculo.unique' => 'Ya existe una unidad con esa placa.',
            'placa_vehiculo.max' => 'La placa no puede superar los 20 caracteres.',
            'estado.required' => 'Seleccione el estado de la unidad.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'placa_vehiculo' => 'placa',
            'tipo_vehiculo' => 'tipo de vehiculo',
            'id_personal' => 'conductor',
            'tramo_actual' => 'tramo actual',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function datosNormalizados(): array
    {
        return [
            'placa_vehiculo' => mb_strtoupper(trim($this->string('placa_vehiculo')->value())),
            'tipo_vehiculo' => $this->string('tipo_vehiculo')->trim()->value(),
            'marca' => $this->input('marca'),
            'modelo' => $this->input('modelo'),
            'anho' => $this->filled('anho') ? (int) $this->input('anho') : null,
            'color' => $this->input('color'),
            'capacidad' => $this->input('capacidad') ?? 0,
            'tara_kg' => $this->input('tara_kg'),
            'peso_bruto_kg' => $this->input('peso_bruto_kg'),
            'peso_neto_kg' => $this->input('peso_neto_kg'),
            'kilometraje' => $this->input('kilometraje') ?? 0,
            'id_personal' => $this->integer('id_personal') ?: null,
            'tramo_actual' => $this->input('tramo_actual'),
            'observaciones' => $this->input('observaciones'),
            'estado' => (int) $this->input('estado'),
        ];
    }
}