<?php

declare(strict_types=1);

namespace App\Http\Requests\Clasificador;

use App\Enums\TipoGasto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta / edicion de un clasificador de gasto (modulo maestro).
 */
class StoreClasificadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('administrar', 'clasificadores');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // `codigo` no se valida porque no se acepta: es correlativo y lo
            // asigna ClasificadorGastoService. Si se admitiera desde el
            // formulario, dos clasificadores podrian compartir codigo y los
            // gastos ya registrados quedarian apuntando a otra fila.
            'descripcion' => ['required', 'string', 'max:200'],
            'tipo_gasto' => ['required', 'string', Rule::in(TipoGasto::values())],
            'afecta_unidad' => ['nullable', 'boolean'],
            'afecta_general' => ['nullable', 'boolean'],
            'estado' => ['nullable', 'string', Rule::in(['ACTIVO', 'INACTIVO'])],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'descripcion.required' => 'La descripcion es obligatoria.',
            'tipo_gasto.required' => 'El tipo de gasto es obligatorio.',
            'afecta_general.required' => 'Marque al menos un alcance: unidad o general.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'afecta_unidad' => 'afecta a unidad',
            'afecta_general' => 'afecta en forma general',
            'tipo_gasto' => 'tipo de gasto',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function datosNormalizados(): array
    {
        $afectaUnidad = $this->boolean('afecta_unidad');
        $afectaGeneral = $this->boolean('afecta_general');

        if (!$afectaUnidad && !$afectaGeneral) {
            $this->merge(['afecta_general' => '0']);

            $afectaGeneral = false;

            $this->validator?->errors()->add(
                'afecta_general',
                'El clasificador debe afectar al menos a una unidad o a la forma general.',
            );
        }

        return [
            // El codigo no viaja desde el formulario. En el alta lo genera el
            // servicio; en la edicion se conserva el que ya tiene la fila,
            // porque los gastos registrados ya lo referencian.
            'descripcion' => $this->string('descripcion')->trim()->value(),
            'tipo_gasto' => $this->string('tipo_gasto')->value(),
            'afecta_unidad' => $afectaUnidad,
            'afecta_general' => $afectaGeneral,
            'estado' => $this->input('estado', 'ACTIVO'),
            'observaciones' => $this->input('observaciones'),
        ];
    }
}