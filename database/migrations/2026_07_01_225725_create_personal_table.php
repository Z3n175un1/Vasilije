<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.personal', function (Blueprint $table) {
            $table->id('id_personal');
            $table->string('ci', 20)->nullable();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('cargo', 50);
            $table->string('telefono', 20)->nullable();
            $table->string('licencia', 50)->nullable();
            $table->decimal('sueldo', 15, 2)->default(0);
            $table->integer('estado')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->index('ci');
            $table->index('cargo');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.personal');
    }
};
