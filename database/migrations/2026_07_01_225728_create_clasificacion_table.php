<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.clasificacion', function (Blueprint $table) {
            $table->id('id_clasificacion');
            $table->unsignedBigInteger('id_vehiculo');
            $table->foreign('id_vehiculo')->references('id_vehiculo')->on('global.vehiculos')->onDelete('cascade');
            $table->boolean('contiene_cantidad')->default(false);
            $table->boolean('relacionado_con_vehiculos')->default(false);
            $table->decimal('combustible', 15, 2)->default(0);
            $table->decimal('gastos_administracion', 15, 2)->default(0);
            $table->decimal('compra_activos', 15, 2)->default(0);
            $table->decimal('varios', 15, 2)->default(0);
            $table->decimal('mantenimiento', 15, 2)->default(0);
            $table->decimal('peajes', 15, 2)->default(0);
            $table->decimal('sueldos', 15, 2)->default(0);
            $table->decimal('viaticos', 15, 2)->default(0);
            $table->decimal('sueldo_total', 15, 2)->default(0);
            $table->decimal('sueldo_previo', 15, 2)->default(0);
            $table->decimal('total_gastos', 15, 2)->default(0);
            $table->timestamp('fecha_clasificacion')->useCurrent();
            $table->string('periodo', 7)->nullable();
            $table->text('observaciones')->nullable();
            $table->index('id_vehiculo');
            $table->index('fecha_clasificacion');
            $table->index('periodo');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.clasificacion');
    }
};
