<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * IDEMPOTENTE.
 *
 * En instalaciones donde la columna `inventario.categoria` ya fue eliminada
 * manualmente (o por una restauracion parcial), el ALTER original fallaba
 * con "columna categoria no existe" y bloqueaba toda la cadena de
 * migraciones, dejando las siguientes sin aplicarse.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('global.inventario', 'categoria')) {
            return; // ya eliminada: nada que hacer
        }

        DB::statement('ALTER TABLE global.inventario ALTER COLUMN categoria DROP NOT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('global.inventario', 'categoria')) {
            return;
        }

        DB::statement('ALTER TABLE global.inventario ALTER COLUMN categoria SET NOT NULL');
    }
};