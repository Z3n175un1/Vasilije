<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SECUENCIAS NATIVAS PARA LA NUMERACION DE DOCUMENTOS.
 *
 * El metodo anterior ("leer el ultimo documento y sumarle 1") es una
 * condicion de carrera: dos peticiones simultaneas leen el mismo maximo y
 * emiten el mismo numero. Tambien retrocede si se anula el ultimo
 * documento, generando duplicados.
 *
 * Una SEQUENCE de PostgreSQL es atomica por definicion y resuelve ambas
 * cosas. El servicio App\Services\DocumentoService las consume con
 * nextval() y las sincroniza una vez con el historico existente.
 */
return new class extends Migration
{
    /** @var array<string, array{string, string, string}> serie => [secuencia, tabla, patron] */
    private array $series = [
        'E' => ['gastos_nro_documento_seq', 'gastos', 'E_%'],
        'I' => ['ingresos_nro_documento_seq', 'ingresos', 'I_%'],
        'GG' => ['gastos_generales_nro_documento_seq', 'gastos_generales', 'GG_%'],
    ];

    public function up(): void
    {
        foreach ($this->series as $serie => [$secuencia, $tabla, $patron]) {
            DB::statement("CREATE SEQUENCE IF NOT EXISTS global.{$secuencia} START WITH 1 INCREMENT BY 1 MINVALUE 1");

            // Continuar desde el historico: nunca por debajo del maximo actual,
            // para no pisar documentos ya emitidos por el metodo anterior.
            $maximo = DB::table("global.{$tabla}")
                ->where('nro_documento', 'like', $patron)
                ->selectRaw("COALESCE(MAX(NULLIF(regexp_replace(nro_documento, '\\D', '', 'g'), '')::bigint), 0) AS m")
                ->value('m');

            $maximo = (int) $maximo;

            if ($maximo > 0) {
                DB::statement("SELECT setval('global.{$secuencia}', ?, false)", [$maximo]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->series as $serie => [$secuencia]) {
            DB::statement("DROP SEQUENCE IF EXISTS global.{$secuencia}");
        }
    }
};