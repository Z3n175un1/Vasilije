<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('global.configuracion', function (Blueprint $table) {
            $table->id('id_configuracion');
            $table->string('llave', 100)->unique();
            $table->text('valor')->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        DB::connection('pgsql')->table('global.configuracion')->insert([
            ['llave' => 'tipo_cambio', 'valor' => '6.96', 'descripcion' => 'Tipo de cambio Bs/$us'],
            ['llave' => 'precio_tonelada_usd', 'valor' => '13', 'descripcion' => 'Precio por tonelada en dólares'],
        ]);
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('global.configuracion');
    }
};
