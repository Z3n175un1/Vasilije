<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.patrimonio', function (Blueprint $table) {
            $table->id('id_patrimonio');
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->decimal('valor_estimado', 15, 2)->default(0);
            $table->string('tipo', 50)->nullable();
            $table->integer('estado')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.patrimonio');
    }
};
