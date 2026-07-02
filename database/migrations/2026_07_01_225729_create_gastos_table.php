<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.gastos', function (Blueprint $table) {
            $table->id('id_gasto');
            $table->unsignedBigInteger('id_vehiculo');
            $table->foreign('id_vehiculo')->references('id_vehiculo')->on('global.vehiculos')->onDelete('cascade');
            $table->unsignedBigInteger('id_clasificacion')->nullable();
            $table->foreign('id_clasificacion')->references('id_clasificacion')->on('global.clasificacion')->onDelete('set null');
            $table->unsignedBigInteger('id_proveedor')->nullable();
            $table->foreign('id_proveedor')->references('id_proveedor')->on('global.proveedores')->onDelete('set null');
            $table->string('tipo_gasto', 50);
            $table->string('concepto', 200);
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 15, 2);
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->string('tipo_combustible', 50)->nullable();
            $table->integer('kilometraje')->nullable();
            $table->string('caseta', 100)->nullable();
            $table->string('ruta', 200)->nullable();
            $table->string('taller', 100)->nullable();
            $table->string('tipo_mantenimiento', 100)->nullable();
            $table->date('fecha_gasto');
            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent();
            $table->string('comprobante', 100)->nullable();
            $table->string('proveedor', 100)->nullable();
            $table->string('tipo_pago', 50)->default('Efectivo');
            $table->string('estado_pago', 20)->default('Pagado');
            $table->text('observaciones')->nullable();
            $table->index('id_vehiculo');
            $table->index('tipo_gasto');
            $table->index('fecha_gasto');
            $table->index('id_clasificacion');
            $table->index('id_proveedor');
            $table->index('estado_pago');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.gastos');
    }
};
