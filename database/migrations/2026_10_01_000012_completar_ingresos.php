<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * COMPLETA `ingresos` PARA QUE EL ESQUEMA LIMPIO COINCIDA CON EL REAL.
 *
 * `create_ingresos_table` no declaraba `fecha_registro`, pero el controlador
 * la escribe en cada flete: sobre una base recien creada el INSERT fallaba
 * con "Undefined column: fecha_registro". Solo funcionaba en la base de
 * desarrollo, donde la columna se habia agregado fuera de las migraciones.
 *
 * Tambien corrige el default de `estado_factura`, que era 'EMITIDA': un
 * valor que no pertenece a App\Enums\EstadoFactura y que ningun filtro
 * consultaba, de modo que esos fletes quedaban fuera tanto del listado de
 * PENDIENTES como de COBRADOS. El ciclo real empieza en PENDIENTE.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('global.ingresos')) {
            return;
        }

        if (!Schema::hasColumn('global.ingresos', 'fecha_registro')) {
            DB::statement(
                'ALTER TABLE global.ingresos ADD COLUMN fecha_registro timestamp DEFAULT CURRENT_TIMESTAMP'
            );
        }

        // Los fletes huerfanos en EMITIDA pasan a PENDIENTE: son validos,
        // simplemente nunca se facturaron.
        DB::statement(
            "UPDATE global.ingresos SET estado_factura = 'PENDIENTE'
             WHERE estado_factura IS NULL OR estado_factura = '' OR estado_factura = 'EMITIDA'"
        );

        DB::statement(
            "ALTER TABLE global.ingresos ALTER COLUMN estado_factura SET DEFAULT 'PENDIENTE'"
        );

        // CHECK coherente con el enum.
        DB::statement('ALTER TABLE global.ingresos DROP CONSTRAINT IF EXISTS ingresos_estado_factura_check');
        DB::statement(
            "ALTER TABLE global.ingresos ADD CONSTRAINT ingresos_estado_factura_check
             CHECK (estado_factura IN ('PENDIENTE','FACTURADA','COBRADO','ANULADA'))"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.ingresos DROP CONSTRAINT IF EXISTS ingresos_estado_factura_check');
        DB::statement("ALTER TABLE global.ingresos ALTER COLUMN estado_factura SET DEFAULT 'EMITIDA'");
    }
};