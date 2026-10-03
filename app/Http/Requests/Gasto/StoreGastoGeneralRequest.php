<?php

declare(strict_types=1);

namespace App\Http\Requests\Gasto;

use App\Services\ClasificadorGastoService;
use App\Services\GastoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gasto GENERAL de la empresa (no atribuible a una unidad).
 *
 * El campo `categoria` desaparece como entrada del usuario: ahora es el
 * `tipo_gasto` del clasificador seleccionado. La columna sigue existiendo
 * porque los reportes la consumen, pero se deriva del clasificador.
 *
 * REGLA: solo clasificadores con `afecta_general = true`.
 */
class StoreGastoGeneralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('crear', 'gastos_generales');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $reglas = GastoService::reglasGeneral();

        $reglas['monto'] = ['required', 'numeric', 'not_in:0'];

        // Misma coherencia de pago que en el gasto de unidad, con una
        // excepcion de dominio: la CAJA CHICA es efectivo en mano y por
        // definicion no sale de una cuenta bancaria.
        $esCredito = $this->input('condicion_pago') === 'CREDITO';
        $esCajaChica = \App\Enums\TipoGasto::normalizar(
            (string) $this->input('tipo_gasto', '')
        ) === \App\Enums\TipoGasto::CajaChica
            || $this->clasificadorEsCajaChica();

        $reglas['id_proveedor'] = [
            $esCredito ? 'required' : 'nullable',
            'integer',
            Rule::exists('proveedores', 'id_proveedor'),
        ];

        $reglas['id_banco'] = [
            ($esCredito || $esCajaChica) ? 'nullable' : 'required',
            'integer',
            Rule::exists('bancos', 'id_banco'),
        ];

        return $reglas + [
            'id_clasificador' => [
                'required',
                'integer',
                Rule::exists('clasificador_gastos', 'id_clasificador')
                    ->where(fn ($q) => $q->where('afecta_general', true)->where('estado', 'ACTIVO')),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_clasificador.required' => 'Seleccione el clasificador del gasto.',
            'id_clasificador.exists' => 'El clasificador seleccionado no aplica a gastos generales.',
            'monto.not_in' => 'El monto no puede ser cero. Use un monto positivo o uno negativo para una devolucion.',
            'condicion_pago.required' => 'Seleccione la condicion de pago.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'id_clasificador' => 'clasificador de gasto',
            'id_banco' => 'cuenta bancaria',
            'id_proveedor' => 'proveedor',
            'nro_comprobante' => 'numero de comprobante',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function datosNormalizados(ClasificadorGastoService $clasificadores): array
    {
        $clasificador = $clasificadores->validarAlcance(
            $this->integer('id_clasificador'),
            'general',
        );

        $monto = (float) $this->input('monto');

        return [
            'id_clasificador' => $clasificador->id_clasificador,
            // `categoria` es el codigo canonico del tipo, no texto libre.
            'categoria' => $clasificador->tipo_gasto,
            'concepto' => $this->string('concepto')->trim()->value(),
            'monto' => round($monto, 2),
            'fecha_gasto' => $this->date('fecha_gasto')->format('Y-m-d'),
            'condicion_pago' => $this->input('condicion_pago'),
            'id_banco' => $this->integer('id_banco') ?: null,
            'id_proveedor' => $this->integer('id_proveedor') ?: null,
            'nro_comprobante' => $this->input('nro_comprobante'),
            'observaciones' => $this->input('observaciones'),
            'es_devolucion' => GastoService::esDevolucion($monto),
        ];
    }

    public function esDevolucion(): bool
    {
        return (float) $this->input('monto') < 0;
    }

    /** El clasificador seleccionado es de tipo Caja Chica. */
    private function clasificadorEsCajaChica(): bool
    {
        if (!$this->filled('id_clasificador')) {
            return false;
        }

        return \App\Enums\TipoGasto::normalizar(
            (string) \Illuminate\Support\Facades\DB::table('global.clasificador_gastos')
                ->where('id_clasificador', $this->integer('id_clasificador'))
                ->value('tipo_gasto')
        ) === \App\Enums\TipoGasto::CajaChica;
    }
}