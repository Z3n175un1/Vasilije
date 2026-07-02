<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.inventario', function (Blueprint $table) {
            $table->id('id_inventario');
            $table->string('codigo', 20)->unique();
            $table->string('nombre_producto', 100);
            $table->text('descripcion')->nullable();
            $table->string('categoria', 50);
            $table->unsignedBigInteger('id_categoria')->nullable();
            $table->foreign('id_categoria')->references('id_categoria')->on('global.categorias_almacen')->onDelete('set null');
            $table->string('unidad_medida', 20)->default('UNIDAD');
            $table->decimal('stock_actual', 12, 2)->default(0);
            $table->decimal('stock_minimo', 12, 2)->default(0);
            $table->decimal('stock_maximo', 12, 2)->default(0);
            $table->decimal('precio_compra', 12, 2)->default(0);
            $table->decimal('precio_venta', 12, 2)->default(0);
            $table->decimal('ultimo_costo', 12, 2)->default(0);
            $table->unsignedBigInteger('id_proveedor')->nullable();
            $table->foreign('id_proveedor')->references('id_proveedor')->on('global.proveedores')->onDelete('set null');
            $table->string('ubicacion_almacen', 100)->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->date('fecha_ultima_compra')->nullable();
            $table->date('fecha_ultima_salida')->nullable();
            $table->string('estado', 20)->default('ACTIVO');
            $table->decimal('peso_kg', 10, 2)->nullable();
            $table->string('marca', 50)->nullable();
            $table->string('modelo', 50)->nullable();
            $table->text('especificaciones')->nullable();
            $table->text('imagen_url')->nullable();
            $table->string('codigo_barras', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->integer('created_by')->nullable();
            $table->index('codigo');
            $table->index('categoria');
            $table->index('nombre_producto');
            $table->index('estado');
            $table->index('stock_actual');
            $table->index('id_proveedor');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.inventario');
    }
};
