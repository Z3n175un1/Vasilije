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
            $table->foreign('id_inventario')->references('id_inventario')->on('global.inventario')->onDelete('cascade');
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
