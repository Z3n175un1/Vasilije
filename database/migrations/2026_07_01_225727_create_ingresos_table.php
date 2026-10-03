<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.ingresos', function (Blueprint $table) {
            $table->id('id_ingreso');
            $table->unsignedBigInteger('id_vehiculo');
            $table->foreign('id_vehiculo')->references('id_vehiculo')->on('global.vehiculos')->onDelete('cascade');
            $table->unsignedBigInteger('id_personal')->nullable();
            $table->foreign('id_personal')->references('id_personal')->on('global.personal')->onDelete('set null');
            $table->string('nro_documento', 20)->nullable();
            $table->string('concepto', 200);
            $table->decimal('monto', 15, 2);
            $table->date('fecha_ingreso')->useCurrent();
            // Momento en que se asento el flete. Existia en la base de
            // desarrollo pero no en la migracion, y el controlador la
            // escribe: una instalacion limpia fallaba al insertar.
            $table->timestamp('fecha_registro')->useCurrent();
            $table->date('fecha_vencimiento')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('cliente_nombre', 200)->nullable();
            $table->string('cliente_nit', 30)->nullable();
            $table->string('cliente_telefono', 30)->nullable();
            $table->string('origen', 200)->nullable();
            $table->string('destino', 200)->nullable();
            $table->decimal('toneladas', 15, 2)->default(0);
            $table->decimal('kilometraje_conducido', 15, 2)->default(0);
            $table->string('conductor_asignado', 255)->nullable();
            $table->string('tipo_pago', 30)->default('EFECTIVO');
            $table->string('estado_factura', 20)->default('EMITIDA');
            $table->string('numero_factura', 50)->nullable();
            $table->date('fecha_factura')->nullable();
            // Ruta aplicada al flete. Existia en la base de desarrollo pero
            // no estaba en la migracion, de modo que una instalacion limpia
            // no podia registrarla. La FK se crea en
            // 2026_10_01_000007_indices_e_integridad.
            $table->unsignedBigInteger('id_tramo')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('id_vehiculo');
            $table->index('id_personal');
            $table->index('fecha_ingreso');
            $table->index('estado_factura');
            $table->index('nro_documento');
            $table->index('numero_factura');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.ingresos');
    }
};
