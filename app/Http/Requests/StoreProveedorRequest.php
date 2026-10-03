<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** Catalogo de proveedores. */
class StoreProveedorRequest extends MaestroRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->can($this->route('id') ? 'editar' : 'crear', 'proveedores');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nit_ci' => [
                'nullable', 'string', 'max:50',
                Rule::unique('proveedores', 'nit_ci')->ignore($id, 'id_proveedor'),
            ],
            'nombre_proveedor' => ['required', 'string', 'max:200'],
            'contacto' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:300'],
            'rubro' => ['nullable', 'string', 'max:100'],
            'tipo_proveedor' => ['nullable', 'string', 'max:50'],
        ];
    }

    /** @return list<string> */
    public function campos(): array
    {
        return ['nit_ci', 'nombre_proveedor', 'contacto', 'telefono', 'email', 'direccion', 'rubro', 'tipo_proveedor'];
    }

    /** @return array<string, mixed> */
    public function datosNormalizados(): array
    {
        $datos = parent::datosNormalizados();

        $datos['tipo_proveedor'] = $datos['tipo_proveedor'] ?? 'GENERAL';
        $datos['estado'] = 1;

        return $datos;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['nit_ci' => 'NIT/CI', 'nombre_proveedor' => 'nombre del proveedor'];
    }
}