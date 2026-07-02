<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.tramos', function (Blueprint $table) {
            $table->id('id_tramo');
            $table->string('origen', 200);
            $table->string('destino', 200);
            $table->decimal('kilometros', 10, 2)->default(0);
            $table->decimal('precio_total', 15, 2)->default(0);
            $table->decimal('gasolina_promedio', 10, 2)->default(0);
            $table->decimal('diesel_promedio', 10, 2)->default(0);
            $table->decimal('gas_promedio', 10, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['origen', 'destino']);
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.tramos');
    }
};
