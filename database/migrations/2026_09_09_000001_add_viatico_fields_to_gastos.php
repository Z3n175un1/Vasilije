<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->table('global.gastos', function (Blueprint $table) {
            if (!Schema::connection('pgsql')->hasColumn('global.gastos', 'tipo_viatico')) {
                $table->string('tipo_viatico', 20)->nullable()->after('tipo_gasto');
            }
            if (!Schema::connection('pgsql')->hasColumn('global.gastos', 'destino_viatico')) {
                $table->string('destino_viatico', 200)->nullable()->after('tipo_viatico');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->table('global.gastos', function (Blueprint $table) {
            $table->dropColumn('tipo_viatico');
        });
    }
};
