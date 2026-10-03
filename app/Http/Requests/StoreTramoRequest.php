<?php

declare(strict_types=1);

namespace App\Http\Requests;

/** Rutas origen -> destino con su tarifado. */
class StoreTramoRequest extends MaestroRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->can($this->route('id') ? 'editar' : 'crear', 'tramos');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'origen' => ['required', 'string', 'max:200'],
            'destino' => ['required', 'string', 'max:200'],
            'kilometros' => ['required', 'numeric', 'min:0'],
            'precio_total' => ['required', 'numeric', 'min:0'],
            'precio_dolar_tonelada' => ['nullable', 'numeric', 'min:0'],
            'gasolina_promedio' => ['nullable', 'numeric', 'min:0'],
            'diesel_promedio' => ['nullable', 'numeric', 'min:0'],
            'gas_promedio' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /** @return list<string> */
    public function campos(): array
    {
        return [
            'origen', 'destino', 'kilometros', 'precio_total',
            'precio_dolar_tonelada', 'gasolina_promedio', 'diesel_promedio', 'gas_promedio',
        ];
    }

    /**
     * La columna real es `kilometros`. `distancia_km` no existe: el visor de
     * nomenclaturas la leia y por eso la columna salia siempre vacia.
     *
     * @return array<string, mixed>
     */
    public function datosNormalizados(): array
    {
        $datos = parent::datosNormalizados();

        if (array_key_exists('distancia_km', $this->all()) && !array_key_exists('kilometros', $datos)) {
            $datos['kilometros'] = $this->input('distancia_km');
        }

        $datos['precio_dolar_tonelada'] ??= 0;

        return $datos;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['kilometros' => 'distancia (km)', 'precio_total' => 'precio total', 'precio_dolar_tonelada' => 'Bs por tonelada'];
    }
}