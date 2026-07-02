<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.movimientos_inventario', function (Blueprint $table) {
            $table->id('id_movimiento');
            $table->unsignedBigInteger('id_inventario');
            $table->foreign('id_inventario')->references('id_inventario')->on('global.inventario')->onDelete('cascade');
            $table->unsignedBigInteger('id_lote')->nullable();
            $table->foreign('id_lote')->references('id_lote')->on('global.lotes')->onDelete('set null');
            $table->string('tipo_movimiento', 20);
            $table->decimal('cantidad', 12, 2);
            $table->decimal('costo_unitario', 12, 2)->nullable();
            $table->decimal('cant_pedida', 12, 2)->default(0);
            $table->integer('id_gasto')->nullable();
            $table->unsignedBigInteger('id_vehiculo')->nullable();
            $table->foreign('id_vehiculo')->references('id_vehiculo')->on('global.vehiculos')->onDelete('set null');
            $table->unsignedBigInteger('id_personal')->nullable();
            $table->foreign('id_personal')->references('id_personal')->on('global.personal')->onDelete('set null');
            $table->string('documento_tipo', 20)->nullable();
            $table->string('documento_numero', 50)->nullable();
            $table->string('nro_doc', 50)->nullable();
            $table->string('proveedor', 100)->nullable();
            $table->date('fecha_movimiento')->useCurrent();
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->integer('registrado_por')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('id_inventario');
            $table->index('tipo_movimiento');
            $table->index('fecha_movimiento');
            $table->index('id_gasto');
            $table->index('id_vehiculo');
            $table->index('id_personal');
            $table->index('id_lote');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.movimientos_inventario');
    }
};
