<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * COMPLETA LAS FKs QUE NO SE PODIAN DECLARAR EN SU MIGRACION ORIGINAL.
 *
 * Que paso
 * --------
 * `create_lotes_table` (2026_07_01_225726) y
 * `create_movimientos_inventario_table` (2026_07_01_225728) declaraban una
 * FK a `global.inventario`, pero `global.inventario` se crea en
 * `2026_07_01_225732`, seis archivos despues.
 *
 * Consecuencia: la cadena de migraciones NUNCA funciono sobre una base de
 * datos limpia; fallaba con
 *     ERROR: no existe la relacion "global.inventario"
 * Solo se advertia porque la base de desarrollo ya tenia todas las tablas
 * creadas al margen de las migraciones.
 *
 * Que hace esta migracion
 * -----------------------
 * Crea esas FKs una vez que `inventario` ya existe. Es idempotente: si la
 * constraint ya esta (instalaciones antiguas que si llegaron a crearla a
 * mano), no hace nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('global.inventario')) {
            return;
        }

        $this->fk(
            'lotes',
            'id_inventario',
            'inventario',
            'id_inventario',
            'global_lotes_id_inventario_foreign',
            'CASCADE'
        );

        $this->fk(
            'movimientos_inventario',
            'id_inventario',
            'inventario',
            'id_inventario',
            'global_movimientos_id_inventario_foreign',
            'CASCADE'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.lotes DROP CONSTRAINT IF EXISTS global_lotes_id_inventario_foreign');
        DB::statement('ALTER TABLE global.movimientos_inventario DROP CONSTRAINT IF EXISTS global_movimientos_id_inventario_foreign');
    }

    private function fk(
        string $tabla,
        string $columna,
        string $tablaRef,
        string $columnaRef,
        string $constraint,
        string $onDelete
    ): void {
        if (!Schema::hasTable('global.' . $tabla)) {
            return;
        }

        $existe = DB::selectOne(
            "SELECT 1 FROM information_schema.table_constraints
             WHERE constraint_schema = 'global' AND table_name = ? AND constraint_name = ?",
            [$tabla, $constraint]
        );

        if ($existe) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE global.%s ADD CONSTRAINT %s
             FOREIGN KEY (%s) REFERENCES global.%s (%s) ON DELETE %s',
            $tabla,
            $constraint,
            $columna,
            $tablaRef,
            $columnaRef,
            $onDelete
        ));
    }
};