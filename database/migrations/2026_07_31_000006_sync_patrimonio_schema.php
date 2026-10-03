<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * IDEMPOTENTE.
 *
 * `create_patrimonio_table` ya declara descripcion, tipo, estado y
 * created_at. Esta migracion los volvia a anadir, con lo que una base
 * limpia fallaba con "Duplicate column: descripcion".
 *
 * En instalaciones antiguas donde la tabla se creo con un esquema mas
 * pobre, sigue anadiendo lo que falte.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('global.patrimonio')) {
            return;
        }

        // La tabla en instalaciones antiguas venia de un dump con
        // fecha_registro en lugar de created_at.
        if (Schema::hasColumn('global.patrimonio', 'fecha_registro')
            && !Schema::hasColumn('global.patrimonio', 'created_at')) {
            DB::statement('ALTER TABLE global.patrimonio ADD COLUMN created_at timestamp DEFAULT CURRENT_TIMESTAMP');
            DB::statement('UPDATE global.patrimonio SET created_at = fecha_registro WHERE created_at IS NULL');
        }

        if (!Schema::hasColumn('global.patrimonio', 'descripcion')) {
            DB::statement('ALTER TABLE global.patrimonio ADD COLUMN descripcion text NULL');
        }

        if (!Schema::hasColumn('global.patrimonio', 'tipo')) {
            DB::statement('ALTER TABLE global.patrimonio ADD COLUMN tipo varchar(50) NULL');
        }

        if (!Schema::hasColumn('global.patrimonio', 'estado')) {
            DB::statement('ALTER TABLE global.patrimonio ADD COLUMN estado integer DEFAULT 1');
        }

        if (Schema::hasColumn('global.patrimonio', 'nombre')) {
            DB::statement('ALTER TABLE global.patrimonio ALTER COLUMN nombre TYPE varchar(200)');
        }
    }

    public function down(): void
    {
        // No se revierte: la tabla se retira despues en
        // 2026_10_01_000009_retirar_tablas_fantasma.
    }
};