<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RESTAURA `ingresos.id_tramo`
 *
 * Registrar un flete fallaba en produccion con:
 *
 *     42703 column "id_tramo" of relation "ingresos" does not exist
 *
 * POR QUE PASA
 * ------------
 * `create_ingresos_table` (batch 1) declara `id_tramo`, y figura como
 * aplicada. Pero la columna no esta en la base: el esquema real se creo
 * antes de que esa migracion incluyera la columna, o se perdio en alguna
 * intervencion manual. El historial de migraciones y el esquema no
 * coinciden, que es la misma divergencia que se aparecio con
 * `costo_total` y con `ingresos.fecha_registro`.
 *
 * `indices_e_integridad` (000007) intento crear el indice y la FK de esta
 * columna, pero sus metodos `indice()` y `fk()` comprueban antes que la
 * columna exista y, si no, no hacen nada. Ese acierto evito que la
 * migracion rompiera, pero tambien hizo que el problema pasara
 * inadvertido: fallo en silencio y el alta de fletes seguio sin funcionar.
 *
 * QUE HACE
 * --------
 * Agrega la columna si falta, con su FK y su indice. Es idempotente: si
 * ya existe, no toca nada. No borra ni modifica datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('global.ingresos')) {
            return;
        }

        if (! Schema::hasColumn('global.ingresos', 'id_tramo')) {
            DB::statement(
                'ALTER TABLE global.ingresos
                 ADD COLUMN id_tramo bigint NULL'
            );
        }

        // La FK solo si `tramos` existe y la columna quedo sin referencia.
        $tieneFk = DB::selectOne("
            SELECT 1 FROM information_schema.table_constraints
            WHERE constraint_schema = 'global'
              AND table_name = 'ingresos'
              AND constraint_name = 'fk_ingreso_tramo'
        ");

        if ($tieneFk === null && Schema::hasTable('global.tramos')) {
            DB::statement(<<<'SQL'
                ALTER TABLE global.ingresos
                ADD CONSTRAINT fk_ingreso_tramo
                FOREIGN KEY (id_tramo) REFERENCES global.tramos (id_tramo)
                ON DELETE SET NULL
                SQL);
        }

        // El indice que 000007 dejo sin crear.
        DB::statement(
            'CREATE INDEX IF NOT EXISTS ingresos_tramo_idx ON global.ingresos (id_tramo)'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('global.ingresos')
            || ! Schema::hasColumn('global.ingresos', 'id_tramo')) {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS global.ingresos_tramo_idx');

        DB::statement(
            'ALTER TABLE global.ingresos DROP CONSTRAINT IF EXISTS fk_ingreso_tramo'
        );

        // Se conserva el dato: puede haber fletes ya asociados a un tramo.
        // Quitar la columna perderia esa informacion.
    }
};