<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.combustible_detalle', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->unsignedBigInteger('id_gasto');
            $table->foreign('id_gasto')->references('id_gasto')->on('global.gastos')->onDelete('cascade');
            $table->string('tipo_carburante', 100);
            $table->decimal('galones', 10, 2);
            $table->decimal('precio_por_galon', 10, 2);
            $table->string('estacion_servicio', 100)->nullable();
            $table->integer('kilometraje_actual')->nullable();
            $table->integer('kilometraje_anterior')->nullable();
            $table->integer('kilometraje_recorrido')->nullable();
            $table->decimal('rendimiento', 10, 2)->nullable();
            $table->index('id_gasto');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.combustible_detalle');
    }
};
