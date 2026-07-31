<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        DB::statement('ALTER TABLE global.gastos DROP CONSTRAINT IF EXISTS fk_gasto_vehiculo');
        DB::statement('ALTER TABLE global.gastos ALTER COLUMN id_personal TYPE integer USING id_personal::integer');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.gastos ADD CONSTRAINT fk_gasto_vehiculo FOREIGN KEY (id_vehiculo) REFERENCES global.vehiculos(id_vehiculo) ON DELETE CASCADE');
        DB::statement('ALTER TABLE global.gastos ALTER COLUMN id_personal TYPE bigint USING id_personal::bigint');
    }
};
