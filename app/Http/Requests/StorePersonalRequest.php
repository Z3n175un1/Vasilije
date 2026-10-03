<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** Catalogo de personal (conductores y administrativos). */
class StorePersonalRequest extends MaestroRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can($this->esUpdate() ? 'editar' : 'crear', 'personal');
    }

    private function esUpdate(): bool
    {
        return $this->route('id') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'ci' => ['nullable', 'string', 'max:20'],
            'cargo' => ['required', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'licencia' => ['nullable', 'string', 'max:50'],
            'sueldo' => ['nullable', 'numeric', 'min:0'],
            'direccion' => ['nullable', 'string', 'max:300'],
            'email' => ['nullable', 'email', 'max:100'],
            'estado' => ['required', 'integer', Rule::in([0, 1])],
        ];
    }

    /** @return list<string> */
    public function campos(): array
    {
        return ['nombres', 'apellidos', 'ci', 'cargo', 'telefono', 'licencia', 'sueldo', 'direccion', 'email', 'estado'];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['ci' => 'C.I.', 'estado' => 'estado'];
    }
}