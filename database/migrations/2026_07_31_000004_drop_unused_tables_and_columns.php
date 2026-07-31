<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.sessions');
        Schema::connection('pgsql')->dropIfExists('global.users');
        Schema::connection('pgsql')->dropIfExists('global.password_reset_tokens');

        DB::statement('ALTER TABLE global.ingresos DROP COLUMN IF EXISTS conductor_asignado');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.ingresos ADD COLUMN conductor_asignado VARCHAR(255)');
    }
};
