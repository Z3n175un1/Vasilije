<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RETIRA SECUENCIAS HUERFANAS.
 *
 * El intento anterior de numeracion atomica (nunca documentado en el
 * historial de migraciones) dejo varias secuencias sueltas. Conviven con
 * las nuevas de 2026_10_01_000004 y confunden: dos mecanismos distintos
 * numerando lo mismo, solo uno en uso.
 *
 * Se verifican primero para no borrar nada ajeno.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $huerfanas = [
        'seq_nro_doc',
        'seq_doc_almacen',
        'seq_doc_egresos',
        'seq_doc_facturas',
        'seq_doc_ingresos',
    ];

    public function up(): void
    {
        foreach ($this->huerfanas as $nombre) {
            $existe = DB::selectOne(
                'SELECT 1 FROM pg_sequences WHERE schemaname = ? AND sequencename = ?',
                ['global', $nombre]
            );

            if ($existe) {
                DB::statement("DROP SEQUENCE IF EXISTS global.{$nombre} CASCADE");
            }
        }
    }

    public function down(): void
    {
        foreach ($this->huerfanas as $nombre) {
            DB::statement("CREATE SEQUENCE IF NOT EXISTS global.{$nombre} START WITH 1 INCREMENT BY 1");
        }
    }
};