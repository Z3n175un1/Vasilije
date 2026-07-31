<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE global.gastos ADD COLUMN IF NOT EXISTS id_banco INTEGER');

        $hasFk = DB::selectOne("SELECT 1 FROM information_schema.table_constraints
            WHERE constraint_schema = 'global' AND table_name = 'gastos'
            AND constraint_name = 'fk_gasto_banco'");
        if (!$hasFk) {
            DB::statement('ALTER TABLE global.gastos
                ADD CONSTRAINT fk_gasto_banco FOREIGN KEY (id_banco)
                REFERENCES global.bancos(id_banco)');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.gastos DROP CONSTRAINT IF EXISTS fk_gasto_banco');
        DB::statement('ALTER TABLE global.gastos DROP COLUMN IF EXISTS id_banco');
    }
};
