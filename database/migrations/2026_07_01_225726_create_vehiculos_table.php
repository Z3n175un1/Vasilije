<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.vehiculos', function (Blueprint $table) {
            $table->id('id_vehiculo');
            $table->string('placa_vehiculo', 15)->unique();
            $table->integer('anho')->nullable();
            $table->string('tipo_vehiculo', 50)->nullable();
            $table->string('marca', 50)->nullable();
            $table->string('modelo', 50)->nullable();
            $table->string('color', 30)->nullable();
            $table->string('conductor', 100)->nullable();
            $table->string('tramo_actual', 100)->nullable();
            $table->decimal('capacidad', 15, 2)->default(0);
            $table->decimal('kilometraje', 15, 2)->default(0);
            $table->unsignedBigInteger('id_personal')->nullable();
            $table->foreign('id_personal')->references('id_personal')->on('global.personal')->onDelete('set null');
            $table->integer('estado')->default(1);
            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent();
            $table->index('placa_vehiculo');
            $table->index('estado');
            $table->index('id_personal');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.vehiculos');
    }
};
