<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->table('global.patrimonio', function ($table) {
            $table->text('descripcion')->nullable()->after('nombre');
            $table->string('tipo', 50)->nullable()->after('valor_estimado');
            $table->integer('estado')->default(1)->after('tipo');
            $table->timestamp('created_at')->useCurrent()->after('estado');
        });

        DB::statement('UPDATE global.patrimonio SET created_at = fecha_registro WHERE created_at IS NULL');

        DB::statement('ALTER TABLE global.patrimonio ALTER COLUMN nombre TYPE varchar(200)');
    }

    public function down(): void
    {
        Schema::connection('pgsql')->table('global.patrimonio', function ($table) {
            $table->dropColumn(['descripcion', 'tipo', 'estado', 'created_at']);
        });
        DB::statement('ALTER TABLE global.patrimonio ALTER COLUMN nombre TYPE varchar(100)');
    }
};
