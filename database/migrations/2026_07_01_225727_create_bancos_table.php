<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.bancos', function (Blueprint $table) {
            $table->id('id_banco');
            $table->string('nombre_banco', 200);
            $table->string('numero_cuenta', 100);
            $table->string('titular', 200);
            $table->string('tipo_cuenta', 50)->default('AHORROS');
            $table->string('moneda', 10)->default('BOB');
            $table->decimal('saldo_inicial', 15, 2)->default(0);
            $table->decimal('saldo_actual', 15, 2)->default(0);
            $table->string('estado', 20)->default('ACTIVO');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.bancos');
    }
};
