<?php

declare(strict_types=1);

namespace App\Http\Requests\Gasto;

use App\Enums\TipoGasto;
use App\Services\GastoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gasto OPERACIONAL de una unidad.
 *
 * REGLA: el gasto se clasifica por GRUPO DE GASTOS. El tipo canonico lo
 * lleva el grupo (columna `tipo_gasto` de `grupos_gastos`): nunca se
 * elige ni se envia, se deriva en el servidor.
 *
 * REGLA DE DEVOLUCION: el monto admite valores negativos. Un monto < 0
 * significa devolucion y se marca como tal (`es_devolucion`).
 */
class StoreGastoUnidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('crear', 'gastos');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $reglas = GastoService::reglasUnidad();

        // El monto cero no es ni un gasto ni una devolucion.
        $reglas['monto'] = ['required', 'numeric', 'not_in:0'];

        // Coherencia de la condicion de pago, resuelta aqui y no en el
        // servicio: si se deja para despues, un POST invalido devuelve un
        // 500 en lugar de un error de validacion.
        $esCredito = $this->input('condicion_pago', 'CONTADO') === 'CREDITO';

        $reglas['id_proveedor'] = [
            $esCredito ? 'required' : 'nullable',
            'integer',
            Rule::exists('proveedores', 'id_proveedor'),
        ];

        $reglas['id_banco'] = [
            $esCredito ? 'nullable' : 'required',
            'integer',
            Rule::exists('bancos', 'id_banco'),
        ];

        // El grupo de gastos debe existir.
        $reglas['id_grupo_gasto'] = [
            'required',
            'integer',
            Rule::exists('grupos_gastos', 'id_grupo_gasto'),
        ];

        // El tipo de gasto NO lo envia el cliente: se deriva del grupo.
        $reglas['tipo_gasto'] = ['nullable', 'string', Rule::in(TipoGasto::values())];

        return $reglas;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_grupo_gasto.required' => 'Seleccione un grupo de gastos.',
            'id_grupo_gasto.exists' => 'El grupo de gastos seleccionado no existe.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser numerico.',
            'monto.not_in' => 'El monto no puede ser cero. Use un monto negativo para registrar una devolucion.',
            'tipo_gasto.in' => 'El tipo de gasto no es valido.',
            'id_vehiculo.exists' => 'La unidad seleccionada no existe.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'id_vehiculo' => 'unidad',
            'id_grupo_gasto' => 'grupo de gastos',
            'id_banco' => 'cuenta bancaria',
            'id_proveedor' => 'proveedor',
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
     * Prepara los datos para el INSERT, derivando lo que antes se hacia
     * a mano en el controlador.
     *
     * @return array<string, mixed>
     */
    public function datosNormalizados(): array
    {
        $grupo = $this->grupo();

        $monto = (float) $this->input('monto');

        return [
            'id_vehiculo' => $this->integer('id_vehiculo'),
            // El clasificador queda como legado: en lo adelante no se
            // asigna, el gasto pertenece a un GRUPO.
            'id_clasificador' => null,
            'id_grupo_gasto' => $grupo->id_grupo_gasto ?? null,
            // Tipo canonico derivado del grupo, nunca del cliente.
            'tipo_gasto' => TipoGasto::normalizar((string) ($grupo->tipo_gasto ?? ''))?->value,
            'concepto' => $this->string('concepto')->trim()->value(),
            'monto' => round($monto, 2),
            'fecha_gasto' => $this->date('fecha_gasto')->format('Y-m-d'),
            'comprobante' => $this->input('comprobante'),
            'cantidad' => $this->input('cantidad') ?? 1,
            'precio_unitario' => $this->input('precio_unitario') ?? 0,
            'kilometraje' => $this->input('kilometraje'),
            'tipo_combustible' => $this->input('tipo_combustible'),
            'id_proveedor' => $this->integer('id_proveedor') ?: null,
            'id_banco' => $this->integer('id_banco') ?: null,
            'id_personal' => $this->integer('id_personal') ?: null,
            'condicion_pago' => $this->input('condicion_pago', 'CONTADO'),
            'metodo_pago' => $this->input('metodo_pago'),
            'fecha_limite_pago' => $this->input('fecha_limite_pago'),
            'observaciones' => $this->input('observaciones'),
            'es_devolucion' => GastoService::esDevolucion($monto),
            // Toda devolucion queda como Anulado: no se puede haber "pagado"
            // algo que se esta devolviendo.
            'estado_pago' => $monto < 0 ? 'Anulado' : 'Pagado',
        ];
    }

    /**
     * Campos del sub-formulario de combustible, si aplica.
     *
     * @return array{0:?string,1:?float,2:?float}
     */
    public function detalleCombustible(): array
    {
        return [
            $this->input('tipo_combustible'),
            $this->filled('litros') ? (float) $this->input('litros') : null,
            $this->filled('precio_por_litro') ? (float) $this->input('precio_por_litro') : null,
        ];
    }

    public function esDevolucion(): bool
    {
        return (float) $this->input('monto') < 0;
    }
}
