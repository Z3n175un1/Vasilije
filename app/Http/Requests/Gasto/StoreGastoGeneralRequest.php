<?php

declare(strict_types=1);

namespace App\Http\Requests\Gasto;

use App\Enums\TipoGasto;
use App\Services\GastoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gasto GENERAL de la empresa (no atribuible a una unidad).
 *
 * REGLA: el gasto se clasifica por GRUPO DE GASTOS. La columna
 * `categoria` se deriva del tipo del grupo (los reportes la consumen),
 * nunca de texto libre ni de un clasificador aparte.
 *
 * REGLA: la CAJA CHICA es efectivo en mano y por definicion no sale de
 * una cuenta bancaria.
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

        // Coherencia de la condicion de pago.
        $esCredito = $this->input('condicion_pago') === 'CREDITO';
        $esCajaChica = $this->grupoEsCajaChica();

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
            'id_grupo_gasto' => [
                'required',
                'integer',
                Rule::exists('grupos_gastos', 'id_grupo_gasto'),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_grupo_gasto.required' => 'Seleccione el grupo del gasto.',
            'id_grupo_gasto.exists' => 'El grupo de gastos seleccionado no existe.',
            'monto.not_in' => 'El monto no puede ser cero. Use un monto positivo o uno negativo para una devolucion.',
            'condicion_pago.required' => 'Seleccione la condicion de pago.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'id_grupo_gasto' => 'grupo de gastos',
            'id_banco' => 'cuenta bancaria',
            'id_proveedor' => 'proveedor',
            'nro_comprobante' => 'numero de comprobante',
        ];
    }

    /** El grupo seleccionado, tal como vive en la base. */
    public function grupo(): ?object
    {
        $id = $this->integer('id_grupo_gasto');

        if ($id <= 0) {
            return null;
        }

        return \Illuminate\Support\Facades\DB::table('grupos_gastos')
            ->where('id_grupo_gasto', $id)
            ->first(['id_grupo_gasto', 'nombre', 'tipo_gasto']);
    }

    /**
     * @return array<string, mixed>
     */
    public function datosNormalizados(): array
    {
        $grupo = $this->grupo();

        $monto = (float) $this->input('monto');
        $tipo = TipoGasto::normalizar((string) ($grupo->tipo_gasto ?? ''))?->value;

        return [
            // El clasificador queda como legado: no se vuelve a asignar.
            'id_clasificador' => null,
            'id_grupo_gasto' => $grupo->id_grupo_gasto ?? null,
            // `categoria` es el codigo canonico derivado del grupo.
            'categoria' => $tipo ?? 'Otro',
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

    /** El grupo seleccionado es de tipo Caja Chica. */
    private function grupoEsCajaChica(): bool
    {
        return \App\Enums\TipoGasto::normalizar(
            (string) ($this->grupo()?->tipo_gasto ?? '')
        ) === \App\Enums\TipoGasto::CajaChica;
    }
}
