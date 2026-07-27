<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        DB::connection('pgsql')->statement('ALTER TABLE global.gastos ALTER COLUMN id_vehiculo DROP NOT NULL');
        DB::connection('pgsql')->statement('ALTER TABLE global.gastos ADD COLUMN id_personal bigint NULL');
        DB::connection('pgsql')->statement('ALTER TABLE global.gastos ADD CONSTRAINT gastos_id_personal_foreign FOREIGN KEY (id_personal) REFERENCES global.personal(id_personal) ON DELETE SET NULL');
        DB::connection('pgsql')->statement('CREATE INDEX gastos_id_personal_index ON global.gastos (id_personal)');
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('DROP INDEX IF EXISTS gastos_id_personal_index');
        DB::connection('pgsql')->statement('ALTER TABLE global.gastos DROP CONSTRAINT IF EXISTS gastos_id_personal_foreign');
        DB::connection('pgsql')->statement('ALTER TABLE global.gastos DROP COLUMN IF EXISTS id_personal');
    }
};
