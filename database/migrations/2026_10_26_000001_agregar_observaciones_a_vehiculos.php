<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Dragones: la solicitud de vehiculos manda `observaciones` en INSERT/UPDATE,
 * pero ninguna migracion creaba la columna en `global.vehiculos`. En una base
 * con data real eso rompia el guardado de unidades con error 42703.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('pgsql')->hasColumn('global.vehiculos', 'observaciones')) {
            Schema::connection('pgsql')->table('global.vehiculos', function ($table) {
                $table->text('observaciones')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('pgsql')->hasColumn('global.vehiculos', 'observaciones')) {
            Schema::connection('pgsql')->table('global.vehiculos', function ($table) {
                $table->dropColumn('observaciones');
            });
        }
    }
};
