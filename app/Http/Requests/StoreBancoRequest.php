<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Support\Facades\DB;

/** Cuentas bancarias de la empresa. */
class StoreBancoRequest extends MaestroRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->can($this->route('id') ? 'editar' : 'crear', 'bancos');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre_banco' => ['required', 'string', 'max:200'],
            'numero_cuenta' => ['required', 'string', 'max:100'],
            'titular' => ['required', 'string', 'max:200'],
            'tipo_cuenta' => ['required', 'string', 'max:50'],
            'moneda' => ['required', 'string', 'max:10'],
            'saldo_inicial' => ['required', 'numeric'],
            'estado' => ['nullable', 'string', 'in:ACTIVO,INACTIVO'],
        ];
    }

    /** @return list<string> */
    public function campos(): array
    {
        return ['nombre_banco', 'numero_cuenta', 'titular', 'tipo_cuenta', 'moneda', 'saldo_inicial'];
    }

    /** @return list<string> */
    public function camposActualizables(): array
    {
        // `saldo_actual` es derivado: se recalcula a partir de `saldo_inicial`
        // y los movimientos CONTADO. Nunca se acepta desde el formulario.
        return $this->campos();
    }

    /** @return array<string, mixed> */
    public function datosNormalizados(): array
    {
        $datos = parent::datosNormalizados();
        $datos['moneda'] = mb_strtoupper($datos['moneda']);
        $datos['estado'] = $datos['estado'] ?? 'ACTIVO';

        if ($this->route('id') === null) {
            $datos['saldo_actual'] = $datos['saldo_inicial'];
        } else {
            // Al editar, el delta del saldo inicial se traslada al saldo actual.
            $actual = DB::table('global.bancos')->where('id_banco', $this->route('id'))->first();

            if ($actual) {
                $datos['saldo_actual'] = round((float) $actual->saldo_actual + ((float) $datos['saldo_inicial'] - (float) $actual->saldo_inicial), 2);
            }
        }

        return $datos;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['numero_cuenta' => 'numero de cuenta', 'saldo_inicial' => 'saldo inicial'];
    }
}