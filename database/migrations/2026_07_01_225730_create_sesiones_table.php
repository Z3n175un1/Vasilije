<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.sesiones', function (Blueprint $table) {
            $table->id('id_sesion');
            $table->unsignedBigInteger('id_usuario');
            $table->foreign('id_usuario')->references('id_usuario')->on('global.usuarios')->onDelete('cascade');
            $table->string('token', 255)->unique();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('fecha_inicio')->useCurrent();
            $table->timestamp('ultima_actividad')->useCurrent();
            $table->timestamp('fecha_expiracion');
            $table->boolean('activo')->default(true);
            $table->string('dispositivo', 100)->nullable();
            $table->index('token');
            $table->index('id_usuario');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.sesiones');
    }
};
