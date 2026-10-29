<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * EL CLASIFICADOR SALE; EL GRUPO QUEDA.
 *
 * El clasificador de gastos se elimina como opcion de usuario: el tipo de
 * gasto vive ahora en el GRUPO. Este cambio de estructuras hace posible
 * esa simplificacion sin perder historia:
 *
 * 1. `grupos_gastos.tipo_gasto` — el tipo canonico (Combustible, Sueldo,
 *    CajaChica...) lo lleva el GRUPO, derivado de sus clasificadores.
 *    Ya nadie elige el tipo aparte ni repite ese dato al registrar.
 * 2. `gastos.id_grupo_gasto` y `gastos_generales.id_grupo_gasto` con
 *    backfill: cada gasto apunta al GRUPO de su clasificador historico,
 *    para que tanto los registros viejos como los nuevos queden coherentes.
 * 3. `ingresos.monto_usd` — el tarifario pasa a dolares (USD/Ton), el
 *    flete guarda su monto en USD ademas del monto en Bs.
 */
return new class extends Migration
{

    public function up(): void
    {
        // 1. Tipo canonico del grupo (desde sus clasificadores).
        if (Schema::hasTable('global.grupos_gastos') && ! Schema::hasColumn('global.grupos_gastos', 'tipo_gasto')) {
            DB::statement(
                'ALTER TABLE global.grupos_gastos ADD COLUMN tipo_gasto varchar(50) NULL'
            );
            DB::statement(
                'COMMENT ON COLUMN global.grupos_gastos.tipo_gasto IS
                 \'Tipo canonico del gasto (Combustible, Sueldo, ...), derivado de sus clasificadores\''
            );
        }

        $this->backfillTipoGrupos();
        $this->enlazar('gastos', 'id_gasto', 'id_gasto');
        $this->enlazar('gastos_generales', 'id_gasto_general', 'id_gasto_general');

        // 3. Fletes en dolares.
        if (Schema::hasTable('global.ingresos') && ! Schema::hasColumn('global.ingresos', 'monto_usd')) {
            DB::statement('ALTER TABLE global.ingresos ADD COLUMN monto_usd numeric(15,2) NULL');
            DB::statement(
                'COMMENT ON COLUMN global.ingresos.monto_usd IS \'Monto del flete en USD (Tarifario USD/Ton x Toneladas)\''
            );
        }
    }

    /**
     * Cada grupo hereda su tipo de los clasificadores que lo componen.
     * Con garantia de buen resultado dado que cada grupo tiene un unico
     * tipo en el backfill original.
     */
    private function backfillTipoGrupos(): void
    {
        if (! Schema::hasTable('global.grupos_gastos')
            || ! Schema::hasColumn('global.grupos_gastos', 'tipo_gasto')
            || ! Schema::hasTable('global.clasificador_gastos')) {
            return;
        }

        DB::statement(
            "UPDATE global.grupos_gastos g
             SET tipo_gasto = c.tipo_gasto
             FROM (
                SELECT id_grupo_gasto, MIN(tipo_gasto) AS tipo_gasto
                FROM global.clasificador_gastos
                WHERE id_grupo_gasto IS NOT NULL AND tipo_gasto IS NOT NULL AND tipo_gasto <> ''
                GROUP BY id_grupo_gasto
            ) c
            WHERE c.id_grupo_gasto = g.id_grupo_gasto
              AND (g.tipo_gasto IS NULL OR g.tipo_gasto = '')"
        );
    }

    /**
     * Enlazar los gastos al grupo de su clasificador historico. La FK es
     * SET NULL DEFERRABLE como en `grupos_de_gastos`: borrar un grupo no
     * borra gastos.
     */
    private function enlazar(string $tabla, string $pk, string $colCreacion): void
    {
        if (! Schema::hasTable('global.' . $tabla)) {
            return;
        }

        if (! Schema::hasColumn('global.' . $tabla, 'id_grupo_gasto')) {
            DB::statement(
                "ALTER TABLE global.{$tabla}
                 ADD COLUMN id_grupo_gasto bigint NULL
                 REFERENCES global.grupos_gastos (id_grupo_gasto)
                 ON DELETE SET NULL
                 DEFERRABLE INITIALLY DEFERRED"
            );
        }

        DB::statement("CREATE INDEX IF NOT EXISTS gastos_{$tabla}_grupo_gasto_idx ON global.{$tabla} (id_grupo_gasto)");

        DB::statement(
            "COMMENT ON COLUMN global.{$tabla}.id_grupo_gasto IS
             'Grupo de gastos al que pertenece el gasto (derivado del clasificador historico)'"
        );

        $this->backfillEnlaces($tabla, $colCreacion);
    }

    /**
     * Backfill: id_grupo_gasto = grupo del clasificador que tenia el gasto.
     */
    private function backfillEnlaces(string $tabla, string $colCreacion): void
    {
        if (! Schema::hasTable('global.clasificador_gastos')
            || ! Schema::hasColumn('global.' . $tabla, 'id_clasificador')) {
            return;
        }

        DB::statement(
            "UPDATE global.{$tabla} gg
             SET id_grupo_gasto = c.id_grupo_gasto
             FROM global.clasificador_gastos c
             WHERE gg.id_clasificador = c.id_clasificador
               AND gg.id_grupo_gasto IS NULL"
        );
    }

    public function down(): void
    {
        foreach (['gastos', 'gastos_generales'] as $tabla) {
            if (Schema::hasTable('global.' . $tabla) && Schema::hasColumn('global.' . $tabla, 'id_grupo_gasto')) {
                DB::statement("ALTER TABLE global.{$tabla} DROP COLUMN id_grupo_gasto");
            }
        }

        if (Schema::hasTable('global.ingresos') && Schema::hasColumn('global.ingresos', 'monto_usd')) {
            DB::statement('ALTER TABLE global.ingresos DROP COLUMN monto_usd');
        }

        if (Schema::hasTable('global.grupos_gastos') && Schema::hasColumn('global.grupos_gastos', 'tipo_gasto')) {
            DB::statement('ALTER TABLE global.grupos_gastos DROP COLUMN tipo_gasto');
        }
    }
};
