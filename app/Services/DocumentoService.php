<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Numeracion atomica y transaccional de documentos.
 *
 * PROBLEMA QUE RESUELVE
 * ---------------------
 * El codigo original hacia esto en cada controlador:
 *
 *   $ultimo = DB::table('gastos')->where('nro_documento','like','E_%')
 *             ->orderBy('id_gasto','desc')->first();
 *   $contador = intval(substr($ultimo->nro_documento, 2)) + 1;
 *
 * Eso es una condicion de carrera: dos peticiones concurrentes leen el
 * mismo "ultimo" y generan el mismo numero. Ademas, si el mas reciente
 * se anula/borra, la secuencia retrocede y produce duplicados.
 *
 * SOLUCION
 * --------
 * Secuencias nativas de PostgreSQL (`nextval`). Son atomicas por
 * definicion, no retroceden, sobreviven a borrados y escalan con
 * escrituras concurrentes sin bloqueos.
 */
final class DocumentoService
{
    /**
     * Prefijo -> secuencia SQL. Las secuencias se crean en la migracion
     * 2026_10_01_000004_crear_secuencias_documento.
     */
    private const SECUENCIAS = [
        'E' => 'gastos_nro_documento_seq',
        'I' => 'ingresos_nro_documento_seq',
        'GG' => 'gastos_generales_nro_documento_seq',
    ];

    private const LARGO = 5;

    /**
     * Numero siguiente para una serie, ya formateado.
     *
     * @param  'E'|'I'|'GG'  $serie
     * @return string  p. ej. "E_00007"
     */
    public function siguiente(string $serie, int $offset = 0): string
    {
        $secuencia = self::SECUENCIAS[$serie] ?? null;

        if ($secuencia === null) {
            throw new RuntimeException("Serie de documento desconocida: {$serie}");
        }

        $this->asegurarSecuencia($serie, $secuencia);

        // nextval() es atomico y no se bloquea entre transacciones.
        $numero = (int) DB::selectOne("SELECT nextval(?) AS v", [$secuencia])->v;

        return $serie . '_' . str_pad((string) ($numero + $offset), self::LARGO, '0', STR_PAD_LEFT);
    }

    /**
     * Empuja la secuencia mas alla del maximo historico.
     *
     * Se usa una sola vez, al instalar el sistema sobre una base que ya
     * tenia documentos generados por el metodo viejo. Sin esto, el primer
     * `nextval` podria colisionar con un documento existente.
     */
    public function sincronizar(string $serie, string $tabla, string $columna, string $patron): void
    {
        $secuencia = self::SECUENCIAS[$serie] ?? null;
        if ($secuencia === null) {
            return;
        }

        $maximo = DB::table($tabla)
            ->where($columna, 'like', $patron)
            ->selectRaw("COALESCE(MAX(NULLIF(regexp_replace({$columna}, '\\D', '', 'g'), '')::bigint), 0) AS m")
            ->value('m');

        $maximo = (int) $maximo;

        if ($maximo > 0) {
            DB::statement("SELECT setval(?, ?, false)", [$secuencia, $maximo]);
        }
    }

    /**
     * Crea la secuencia si aun no existe (defensa ante installs parciales).
     */
    private function asegurarSecuencia(string $serie, string $secuencia): void
    {
        $existe = DB::selectOne(
            "SELECT 1 FROM information_schema.sequences
             WHERE sequence_schema = current_schema() AND sequence_name = ?",
            [$secuencia]
        );

        if (!$existe) {
            DB::statement('CREATE SEQUENCE IF NOT EXISTS ' . $secuencia . ' START WITH 1 INCREMENT BY 1');
        }
    }
}
