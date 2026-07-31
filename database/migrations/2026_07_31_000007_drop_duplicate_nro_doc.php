<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        DB::statement('UPDATE global.movimientos_inventario SET documento_numero = nro_doc WHERE documento_numero IS NULL AND nro_doc IS NOT NULL AND nro_doc <> \'\'');
        DB::statement('ALTER TABLE global.movimientos_inventario DROP COLUMN IF EXISTS nro_doc');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.movimientos_inventario ADD COLUMN nro_doc varchar(50)');
        DB::statement('UPDATE global.movimientos_inventario SET nro_doc = documento_numero');
    }
};
