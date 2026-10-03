<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** Items / productos del inventario. */
class StoreItemRequest extends MaestroRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->can($this->route('id') ? 'editar' : 'crear', 'items');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'codigo' => [
                'nullable', 'string', 'max:20',
                Rule::unique('inventario', 'codigo')->ignore($id, 'id_inventario'),
            ],
            'nombre_producto' => ['required', 'string', 'max:100'],
            'id_categoria' => ['nullable', 'integer', Rule::exists('categorias_almacen', 'id_categoria')],
            'unidad_medida' => ['required', 'string', 'max:20'],
            'stock_actual' => ['nullable', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'stock_maximo' => ['nullable', 'numeric', 'min:0'],
            'precio_compra' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
            'id_proveedor' => ['nullable', 'integer', Rule::exists('proveedores', 'id_proveedor')],
            'marca' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'codigo_barras' => ['nullable', 'string', 'max:50'],
            'estado' => ['nullable', 'string', 'in:ACTIVO,INACTIVO'],
        ];
    }

    /** @return list<string> */
    public function campos(): array
    {
        return [
            'codigo', 'nombre_producto', 'id_categoria', 'unidad_medida',
            'stock_actual', 'stock_minimo', 'stock_maximo',
            'precio_compra', 'precio_venta', 'id_proveedor',
            'marca', 'descripcion', 'codigo_barras',
        ];
    }

    /**
     * `stock_actual` NO se edita desde el formulario de edicion: el stock es
     * consecuencia de los movimientos, no una cifra que se escriba a mano.
     * Editarlo aqui desincronizaba el inventario del kardex.
     *
     * @return list<string>
     */
    public function camposActualizables(): array
    {
        return array_values(array_diff($this->campos(), ['stock_actual']));
    }

    /** @return array<string, mixed> */
    public function datosNormalizados(): array
    {
        $datos = parent::datosNormalizados();

        $datos['estado'] ??= 'ACTIVO';
        $datos['stock_minimo'] ??= 0;
        $datos['precio_compra'] ??= 0;
        $datos['precio_venta'] ??= 0;
        $datos['id_proveedor'] = $this->filled('id_proveedor') ? $this->integer('id_proveedor') : null;

        return $datos;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'nombre_producto' => 'nombre del producto',
            'id_categoria' => 'grupo',
            'unidad_medida' => 'unidad de medida',
            'stock_minimo' => 'stock minimo',
            'precio_compra' => 'precio de compra',
        ];
    }
}