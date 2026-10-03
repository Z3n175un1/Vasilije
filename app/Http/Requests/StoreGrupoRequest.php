<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** Grupos / categorias del almacen. */
class StoreGrupoRequest extends MaestroRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->can($this->route('id') ? 'editar' : 'crear', 'grupos');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('categorias_almacen', 'nombre')->ignore($id, 'id_categoria'),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return list<string> */
    public function campos(): array
    {
        return ['nombre', 'descripcion'];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['nombre' => 'nombre del grupo'];
    }
}