<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\ConfiguracionService;

/** Rutas origen -> destino con su tarifado EN DOLARES (US$/Ton). */

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
            // El tarifario se define en dolares; el precio total en Bs ya no
            // se pide: se calcula (US$/Ton x TC) solo para compatibilidad.
            'precio_dolar_tonelada' => ['required', 'numeric', 'min:0'],
            'precio_total' => ['nullable', 'numeric', 'min:0'],
            'gasolina_promedio' => ['nullable', 'numeric', 'min:0'],
            'diesel_promedio' => ['nullable', 'numeric', 'min:0'],
            'gas_promedio' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'precio_dolar_tonelada.required' => 'La tarifa por tonelada (US$) es obligatoria.',
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

        // La tarifa del tramo es US$/Ton (obligatoria). El precio total en
        // Bs es un dato de compatibilidad: si no llega del formulario se
        // calcula con el tipo de cambio de trabajo.
        $dolar = (float) ($datos['precio_dolar_tonelada'] ?? 0);
        $tc = app(ConfiguracionService::class)->tipoCambio();

        $datos['precio_dolar_tonelada'] = $dolar;
        $datos['precio_total'] = $this->filled('precio_total')
            ? round((float) $this->input('precio_total'), 2)
            : round($dolar * $tc, 2);

        return $datos;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'kilometros' => 'distancia (km)',
            'precio_total' => 'precio total (Bs)',
            'precio_dolar_tonelada' => 'tarifa por tonelada (US$)',
        ];
    }
}