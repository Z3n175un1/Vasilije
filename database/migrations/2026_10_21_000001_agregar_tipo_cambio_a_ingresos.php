<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TIPO DE CAMBIO CON EL QUE SE FACTURO CADA FLETE.
 *
 * De los reportes surge la pregunta "con cuantos Bs/USD se facturo este
 * flete?" y hasta ahora la respuesta era calcular hacia atras con la
 * configuracion vigente HOY, que cambia: el dato historico no quedaba.
 *
 * Agrega `ingresos.tipo_cambio` (Bs por USD al registrarse el flete). Es
 * nullable: los fletes anteriores no tienen el dato, el listado muestra
 * BS.x --- y no un cero inventado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('global.ingresos')) {
            return;
        }

        if (! Schema::hasColumn('global.ingresos', 'tipo_cambio')) {
            DB::statement(
                'ALTER TABLE global.ingresos
                 ADD COLUMN tipo_cambio numeric(15,2) NULL'
            );
        }

        DB::statement('COMMENT ON COLUMN global.ingresos.tipo_cambio IS \'Tipo de cambio Bs/USD al momento de registrar el flete\'');
    }

    public function down(): void
    {
        if (! Schema::hasTable('global.ingresos')
            || ! Schema::hasColumn('global.ingresos', 'tipo_cambio')) {
            return;
        }

        DB::statement('ALTER TABLE global.ingresos DROP COLUMN tipo_cambio');
    }
};
