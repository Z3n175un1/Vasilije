<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->table('global.gastos', function (Blueprint $table) {
            if (!Schema::connection('pgsql')->hasColumn('global.gastos', 'nro_documento')) {
                $table->string('nro_documento', 20)->nullable()->after('id_gasto');
                $table->index('nro_documento');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->table('global.gastos', function (Blueprint $table) {
            $table->dropColumn('nro_documento');
        });
    }
};
