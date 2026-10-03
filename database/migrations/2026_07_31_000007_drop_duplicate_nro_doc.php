<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        // IDEMPOTENTE: en instalaciones limpias la columna `nro_doc` ya no
        // existe (create_movimientos_inventario_table no la declara), de
        // modo que consultarla a secas reventaba con "Undefined column".
        $tiene = \Illuminate\Support\Facades\Schema::hasColumn('global.movimientos_inventario', 'nro_doc');

        if (!$tiene) {
            return;
        }

        DB::statement(
            "UPDATE global.movimientos_inventario
             SET documento_numero = nro_doc
             WHERE documento_numero IS NULL AND nro_doc IS NOT NULL AND nro_doc <> ''"
        );

        DB::statement('ALTER TABLE global.movimientos_inventario DROP COLUMN IF EXISTS nro_doc');
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('global.movimientos_inventario', 'nro_doc')) {
            return;
        }

        DB::statement('ALTER TABLE global.movimientos_inventario ADD COLUMN nro_doc varchar(50)');
        DB::statement('UPDATE global.movimientos_inventario SET nro_doc = documento_numero');
    }
};
