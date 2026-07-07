<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->table('global.vehiculos', function (Blueprint $table) {
            $table->decimal('tara_kg', 10, 2)->nullable()->after('capacidad');
            $table->decimal('peso_bruto_kg', 10, 2)->nullable()->after('tara_kg');
            $table->decimal('peso_neto_kg', 10, 2)->nullable()->after('peso_bruto_kg');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->table('global.vehiculos', function (Blueprint $table) {
            $table->dropColumn(['tara_kg', 'peso_bruto_kg', 'peso_neto_kg']);
        });
    }
};
