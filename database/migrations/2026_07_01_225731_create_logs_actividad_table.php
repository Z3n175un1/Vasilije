<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.logs_actividad', function (Blueprint $table) {
            $table->id('id_log');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->foreign('id_usuario')->references('id_usuario')->on('global.usuarios')->onDelete('set null');
            $table->string('usuario', 50)->nullable();
            $table->string('accion', 100);
            $table->string('modulo', 50)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->jsonb('datos_adicionales')->nullable();
            $table->timestamp('fecha_evento')->useCurrent();
            $table->index('id_usuario');
            $table->index('fecha_evento');
            $table->index('accion');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.logs_actividad');
    }
};
