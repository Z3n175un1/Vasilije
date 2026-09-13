<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('pgsql')->create('global.gastos_generales', function (Blueprint $table) {
            $table->id('id_gasto_general');
            $table->string('nro_documento', 20);
            $table->string('categoria', 50);
            $table->string('concepto');
            $table->decimal('monto', 12, 2);
            $table->date('fecha_gasto');
            $table->string('condicion_pago', 20)->default('CONTADO');
            $table->foreignId('id_banco')->nullable();
            $table->foreignId('id_proveedor')->nullable();
            $table->string('nro_comprobante', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)->default('ACTIVO');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.gastos_generales');
    }
};
