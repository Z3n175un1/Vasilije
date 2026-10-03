<?php

declare(strict_types=1);

namespace App\Http\Requests\Gasto;

use App\Enums\TipoGasto;
use App\Services\GastoService;
use App\Services\ClasificadorGastoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gasto OPERACIONAL de una unidad.
 *
 * REGLA NUEVA: el tipo de gasto no se elige de una lista hardcodeada en la
 * vista, sino a traves de un CLASIFICADOR. Solo se ofrecen los
 * clasificadores con `afecta_unidad = true`.
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

        // El clasificador debe existir y aplicar a unidades.
        $reglas['id_clasificador'] = [
            'required',
            'integer',
            Rule::exists('clasificador_gastos', 'id_clasificador')
                ->where(fn ($q) => $q->where('afecta_unidad', true)->where('estado', 'ACTIVO')),
        ];

        // El tipo de gasto NO lo elige el usuario: se deriva del clasificador.
        // Si el cliente lo envia, solo se comprueba que sea coherente con el
        // clasificador; enviarlo no es obligatorio (asi la UI no duplica el
        // dato y no pueden quedar desincronizados).
        $reglas['tipo_gasto'] = ['nullable', 'string', Rule::in(TipoGasto::values())];
        $reglas['tipo_gasto'][] = function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || !$this->filled('id_clasificador')) {
                return;
            }

            $clasificador = app(ClasificadorGastoService::class)->buscar((int) $this->input('id_clasificador'));

            if ($clasificador && $clasificador->tipo_gasto !== $value) {
                $fail("El tipo de gasto debe coincidir con el clasificador seleccionado ({$clasificador->tipo_gasto}).");
            }
        };

        return $reglas;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_clasificador.required' => 'Seleccione un clasificador de gasto.',
            'id_clasificador.exists' => 'El clasificador seleccionado no aplica a gastos de unidad.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser numerico.',
            'tipo_gasto.in' => 'El tipo de gasto no es valido.',
            'id_vehiculo.exists' => 'La unidad seleccionada no existe.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'id_vehiculo' => 'unidad',
            'id_clasificador' => 'clasificador de gasto',
            'tipo_gasto' => 'tipo de gasto',
            'id_banco' => 'cuenta bancaria',
            'id_proveedor' => 'proveedor',
        ];
    }

    /**
     * Prepara los datos para el INSERT, derivando lo que antes se hacia
     * a mano en el controlador.
     *
     * @return array<string, mixed>
     */
    public function datosNormalizados(ClasificadorGastoService $clasificadores): array
    {
        $clasificador = $clasificadores->validarAlcance(
            $this->integer('id_clasificador'),
            'unidad',
        );

        $monto = (float) $this->input('monto');

        return [
            'id_vehiculo' => $this->integer('id_vehiculo'),
            'id_clasificador' => $clasificador->id_clasificador,
            'tipo_gasto' => $clasificador->tipo_gasto,
            'concepto' => $this->string('concepto')->trim()->value(),
            'monto' => round($monto, 2),
            'fecha_gasto' => $this->date('fecha_gasto')->format('Y-m-d'),
            'descripcion' => $this->input('descripcion'),
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