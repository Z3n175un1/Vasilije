<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE global.gastos DROP CONSTRAINT IF EXISTS gastos_tipo_gasto_check');
        DB::statement("ALTER TABLE global.gastos ADD CONSTRAINT gastos_tipo_gasto_check CHECK (tipo_gasto IN ('Combustible', 'Administracion', 'Compra_Activos', 'Varios', 'Mantenimiento', 'Peaje', 'Sueldos', 'Viaticos', 'Seguro', 'Lubricante', 'Llantas', 'Otro'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.gastos DROP CONSTRAINT IF EXISTS gastos_tipo_gasto_check');
        DB::statement("ALTER TABLE global.gastos ADD CONSTRAINT gastos_tipo_gasto_check CHECK (tipo_gasto IN ('Combustible', 'Administracion', 'Compra_Activos', 'Varios', 'Mantenimiento', 'Peaje', 'Sueldos', 'Viaticos'))");
    }
};
