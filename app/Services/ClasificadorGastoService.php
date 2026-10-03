<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TipoGasto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Catalogo de clasificadores de gasto.
 *
 * QUE ES UN CLASIFICADOR
 * ----------------------
 * Antes, el "tipo de gasto" estaba hardcodeado en cada vista como un
 * <select> con valores literales, y el mismo concepto se escribia de
 * tres formas distintas segun el modulo:
 *
 *   gastos/form.blade.php          -> Combustible, Mantenimiento, Peaje...
 *   gastos-generales/form.blade    -> 'Caja Chiva'  (con typo)
 *   GastoGeneralController         -> 'Caja Chica,Servicios B'sicos,...' (mojibake)
 *
 * Cada uno con su propia regla de validacion, y el resultado era que un
 * gasto de unidad podia grabarse con un tipo que la BD rechazaba.
 *
 * El clasificador centraliza eso en UNA tabla administrable, con la
 * dimension de alcance que faltaba:
 *
 *   afecta_unidad   -> visible al registrar un gasto contra una UNIDAD
 *   afecta_general  -> visible al registrar un GASTO GENERAL
 */
final class ClasificadorGastoService
{
    public const CODIGO_PREFIJO = 'CG-';

    /**
     * Listado filtrado por alcance.
     *
     * @param  'unidad'|'general'|'todos'  $alcance
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function listar(string $alcance = 'todos', bool $soloActivos = true): \Illuminate\Support\Collection
    {
        $q = DB::table('global.clasificador_gastos');

        match ($alcance) {
            'unidad' => $q->where('afecta_unidad', true),
            'general' => $q->where('afecta_general', true),
            default => null,
        };

        if ($soloActivos) {
            $q->where('estado', 'ACTIVO');
        }

        return $q->orderBy('tipo_gasto')->orderBy('descripcion')->get();
    }

    public function buscar(int $id): ?object
    {
        return DB::table('global.clasificador_gastos')->where('id_clasificador', $id)->first();
    }

    /**
     * Valida que el clasificador exista y tenga el alcance requerido.
     *
     * @param  'unidad'|'general'  $alcance
     */
    public function validarAlcance(?int $id, string $alcance): object
    {
        if ($id === null) {
            throw ValidationException::withMessages([
                'id_clasificador' => 'Debe seleccionar un clasificador de gasto.',
            ]);
        }

        $clasificador = $this->buscar($id);

        if (!$clasificador) {
            throw ValidationException::withMessages([
                'id_clasificador' => 'El clasificador de gasto seleccionado no existe.',
            ]);
        }

        $columna = $alcance === 'unidad' ? 'afecta_unidad' : 'afecta_general';

        if (!$clasificador->$columna) {
            throw ValidationException::withMessages([
                'id_clasificador' => sprintf(
                    'El clasificador "%s" (%s) no aplica a %s.',
                    $clasificador->descripcion,
                    $clasificador->tipo_gasto,
                    $alcance === 'unidad' ? 'gastos de unidad' : 'gastos generales',
                ),
            ]);
        }

        return $clasificador;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function crear(array $datos): int
    {
        $datos = $this->validar($datos);

        return DB::table('global.clasificador_gastos')->insertGetId([
            // El codigo es siempre correlativo: nunca se toma de la peticion.
            'codigo' => $this->siguienteCodigo(),
            'descripcion' => $datos['descripcion'],
            'tipo_gasto' => $datos['tipo_gasto'],
            'afecta_unidad' => $datos['afecta_unidad'] ?? false,
            'afecta_general' => $datos['afecta_general'] ?? true,
            'estado' => 'ACTIVO',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_clasificador');
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(int $id, array $datos): void
    {
        $datos = $this->validar($datos, $id);

        DB::table('global.clasificador_gastos')
            ->where('id_clasificador', $id)
            ->update([
                // El codigo no se toca: es la referencia que tienen los gastos
                // ya registrados. Cambiarlo dejaria esos gastos apuntando a un
                // clasificador distinto del que el operador cree.
                'descripcion' => $datos['descripcion'],
                'tipo_gasto' => $datos['tipo_gasto'],
                'afecta_unidad' => $datos['afecta_unidad'] ?? false,
                'afecta_general' => $datos['afecta_general'] ?? true,
                'estado' => $datos['estado'] ?? 'ACTIVO',
                'updated_at' => now(),
            ]);
    }

    /**
     * Baja logica. No se borra fisicamente porque historicamente hay
     * gastos que lo referencian.
     */
    public function desactivar(int $id): bool
    {
        $enUso = DB::table('global.gastos')->where('id_clasificador', $id)->exists()
            || DB::table('global.gastos_generales')->where('id_clasificador', $id)->exists();

        if ($enUso) {
            DB::table('global.clasificador_gastos')
                ->where('id_clasificador', $id)
                ->update(['estado' => 'INACTIVO', 'updated_at' => now()]);

            return false; // desactivado, no eliminado
        }

        DB::table('global.clasificador_gastos')->where('id_clasificador', $id)->delete();

        return true; // eliminado
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function validar(array $datos, ?int $ignorarId = null): array
    {
        $afectaUnidad = (bool) ($datos['afecta_unidad'] ?? false);
        $afectaGeneral = (bool) ($datos['afecta_general'] ?? true);

        if (!$afectaUnidad && !$afectaGeneral) {
            throw ValidationException::withMessages([
                'afecta_general' => 'El clasificador debe afectar al menos a una unidad o a la forma general.',
            ]);
        }

        // El tipo debe pertenecer al catalogo canonico.
        $tipo = TipoGasto::tryFrom($datos['tipo_gasto']);
        if ($tipo === null) {
            throw ValidationException::withMessages([
                'tipo_gasto' => 'Tipo de gasto no valido.',
            ]);
        }

        return [
            'descripcion' => $datos['descripcion'],
            'tipo_gasto' => $tipo->value,
            'afecta_unidad' => $afectaUnidad,
            'afecta_general' => $afectaGeneral,
            'estado' => $datos['estado'] ?? 'ACTIVO',
        ];
    }

/**
 * Próximo código correlativo del catálogo, con formato CG-0001.
 *
 * Se expone para que el formulario muestre al operador el código que va a
 * recibir su registro: es informativo, el valor definitivo lo decide el
 * servidor al crear (dentro de una transacción, para que dos altas
 * simultáneas no saquen el mismo número).
 */
public function siguienteCodigo(): string
{
    return self::CODIGO_PREFIJO . str_pad(
        (string) ($this->maximoCodigoNumerico() + 1),
        4,
        '0',
        STR_PAD_LEFT
    );
}

/**
 * Mayor número contenido en los códigos existentes, o 0 si no hay ninguno.
 *
 * Se leen los dígitos del código, no la longitud del id: si un clasificador
 * se elimina, el id se reutiliza pero el código nunca retrocede, que es lo
 * que espera el operador cuando ve CG-0023.
 */
private function maximoCodigoNumerico(): int
{
    $maximo = DB::table('global.clasificador_gastos')
        ->selectRaw(
            "COALESCE(MAX(NULLIF(regexp_replace(codigo, '\\D', '', 'g'), '')::bigint), 0) AS m"
        )
        ->value('m');

    return (int) $maximo;
}
}
