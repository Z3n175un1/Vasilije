<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.lotes', function (Blueprint $table) {
            $table->id('id_lote');
            $table->unsignedBigInteger('id_inventario');
            // NOTA: la FK a global.inventario NO se declara aqui.
            //
            // En el orden original este archivo se ejecutaba ANTES que
            // 2026_07_01_225732_create_inventario_table, de modo que una base
            // recien creada fallaba con:
            //     ERROR: no existe la relacion "global.inventario"
            // El unico motivo por el que nunca se noto es que la base de
            // desarrollo ya tenia las tablas creadas fuera de las
            // migraciones. La FK se agrega ahora en
            // 2026_10_01_000011_completar_fks_inventario.
            $table->string('codigo_lote', 50);
            $table->date('fecha_ingreso')->useCurrent();
            $table->decimal('cantidad_inicial', 12, 2)->default(0);
            $table->decimal('cantidad_actual', 12, 2)->default(0);
            $table->decimal('precio_compra', 12, 2)->default(0);
            $table->string('estado', 20)->default('ACTIVO');
            $table->timestamp('created_at')->useCurrent();
            $table->index('id_inventario');
            $table->index('codigo_lote');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.lotes');
    }
};
