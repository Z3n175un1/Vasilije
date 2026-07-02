<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.proveedores', function (Blueprint $table) {
            $table->id('id_proveedor');
            $table->string('nit_ci', 20)->unique()->nullable();
            $table->string('nombre_proveedor', 200);
            $table->string('contacto', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('direccion')->nullable();
            $table->string('rubro', 100)->nullable();
            $table->string('tipo_proveedor', 50)->default('GENERAL');
            $table->integer('estado')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->index('nombre_proveedor');
            $table->index('nit_ci');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.proveedores');
    }
};
