<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base para los CRUD de catalogos (personal, proveedores, bancos, rutas,
 * grupos, items).
 *
 * RESUELVE UN PROBLEMA REPETIDO
 * -----------------------------
 * Cada controlador hacia esto:
 *
 *   $data = $request->validate([...]);
 *   $allowed = ['nombres', 'apellidos', ...];
 *   $data = array_filter($data, fn ($k) => in_array($k, $allowed), ARRAY_FILTER_USE_KEY);
 *   DB::table('global.personal')->insert($data);
 *
 * La lista blanca se copiaba a mano y se desincronizaba de las reglas: un
 * campo nuevo validado pero no whitelisted se descartaba en silencio, y uno
 * whitelisted pero no validado se insertaba crudo.
 *
 * Aqui `campos()` es la unica declaracion: si el campo no esta en `campos()`,
 * no llega al INSERT.
 */
abstract class MaestroRequest extends FormRequest
{
    /**
     * Columnas que pueden escribirse. Define el contrato con la base.
     *
     * @return list<string>
     */
    abstract public function campos(): array;

    /**
     * Columnas editables. Por defecto las mismas que se crean.
     *
     * @return list<string>
     */
    public function camposActualizables(): array
    {
        return $this->campos();
    }

    /**
     * @return array<string, mixed>
     */
    public function datosNormalizados(): array
    {
        $datos = $this->safe()->only($this->campos());

        // Normalizar cadenas: recortar y convertir vacio a NULL.
        foreach ($datos as $clave => $valor) {
            if (is_string($valor)) {
                $datos[$clave] = trim($valor) === '' ? null : trim($valor);
            }
        }

        return array_filter($datos, static fn ($v) => $v !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function datosActualizables(): array
    {
        $datos = $this->safe()->only($this->camposActualizables());

        foreach ($datos as $clave => $valor) {
            if (is_string($valor)) {
                $datos[$clave] = trim($valor) === '' ? null : trim($valor);
            }
        }

        return array_filter($datos, static fn ($v) => $v !== null);
    }

    /**
     * @return array<string, string>
     */
    protected function credencialesObligatorias(): array
    {
        return [];
    }
}