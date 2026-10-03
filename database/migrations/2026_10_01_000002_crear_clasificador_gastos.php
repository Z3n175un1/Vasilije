<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CATALOGO MAESTRO DE CLASIFICADORES DE GASTO.
 *
 * Reemplaza los <select> con valores literales que cada vista repetia
 * (y que entre si no coincidian) por una tabla administrable con la
 * dimension de alcance que faltaba:
 *
 *   afecta_unidad   -> el clasificador aparece al registrar un gasto de UNIDAD
 *   afecta_general  -> el clasificador aparece al registrar un GASTO GENERAL
 *
 * `tipo_gasto` queda FOREIGN KEY contra el catalogo canonico; no se
 * duplica el CHECK, porque el CHECK de gastos.tipo_gasto ya define ese
 * catalogo (ver 2026_10_01_000001).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('global.clasificador_gastos')) {
            Schema::connection('pgsql')->create('global.clasificador_gastos', function (Blueprint $table) {
                $table->id('id_clasificador');
                $table->string('codigo', 20)->unique();              // Codigo ID  (CG-0001)
                $table->string('descripcion', 200);                 // Descripcion
                $table->string('tipo_gasto', 50);                   // Tipo de Gasto
                $table->boolean('afecta_unidad')->default(false);    // Afecta a Unidad
                $table->boolean('afecta_general')->default(true);    // Afecta en forma General
                $table->string('estado', 20)->default('ACTIVO');
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index('tipo_gasto', 'clasificador_tipo_gasto_idx');
                $table->index(['afecta_unidad', 'afecta_general'], 'clasificador_alcance_idx');
                $table->index('estado');
            });
        }

        // La FK al tipo canonico se agrega como constraint nombrado para poder
        // revertirla limpiamente en down().
        DB::statement('ALTER TABLE global.clasificador_gastos DROP CONSTRAINT IF EXISTS clasificador_tipo_gasto_fkey');
        DB::statement(
            'ALTER TABLE global.clasificador_gastos
             ADD CONSTRAINT clasificador_tipo_gasto_fkey
             CHECK (tipo_gasto IN (
                ' . "'" . implode("','", [
                    'Combustible', 'Mantenimiento', 'Peaje', 'Lubricante', 'Llantas',
                    'Seguro', 'Sueldo', 'Viatico', 'CajaChica', 'ServiciosBasicos',
                    'Impuestos', 'Telecomunicaciones', 'Alquiler', 'Administrativo',
                    'CompraActivos', 'Varios', 'Otro',
                ]) . "'" . '))'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.clasificador_gastos DROP CONSTRAINT IF EXISTS clasificador_tipo_gasto_fkey');
        Schema::connection('pgsql')->dropIfExists('global.clasificador_gastos');
    }
};