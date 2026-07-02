<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('usuario', 50)->unique();
            $table->string('email', 100)->unique();
            $table->string('contrasenha', 255);
            $table->string('nombres', 100)->nullable();
            $table->string('apellidos', 100)->nullable();
            $table->string('documento_identidad', 20)->unique()->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('rol', 20)->default('operador');
            $table->integer('estado')->default(1);
            $table->integer('intentos_fallidos')->default(0);
            $table->timestamp('bloqueado_hasta')->nullable();
            $table->timestamp('ultimo_login')->nullable();
            $table->string('ultimo_ip', 45)->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrent();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->foreign('creado_por')->references('id_usuario')->on('global.usuarios')->onDelete('set null');
            $table->string('reset_token', 255)->nullable();
            $table->timestamp('reset_token_expira')->nullable();
            $table->string('tema', 20)->default('claro');
            $table->boolean('notificaciones')->default(true);
            $table->text('observaciones')->nullable();
            $table->index('usuario');
            $table->index('email');
            $table->index('documento_identidad');
            $table->index('estado');
            $table->index('rol');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.usuarios');
    }
};
