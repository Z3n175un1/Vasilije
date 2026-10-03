<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `costo_total` COMO COLUMNA GENERADA
 *
 * En la base de desarrollo `movimientos_inventario.costo_total` esta
 * declarada como GENERATED ALWAYS AS (cantidad * costo_unitario), pero el
 * historial de migraciones la creaba como decimal normal. De ahi la
 * divergencia:
 *
 *   - En la base real, enviar `costo_total` en el INSERT falla con
 *     "no se puede insertar un valor no-predeterminado en la columna
 *     «costo_total»", y con eso caia el registro de cada compra.
 *   - En una base creada desde cero por las migraciones, la columna era
 *     editable, asi que el test suite nunca vio ese fallo.
 *
 * El valor unico de una columna generada es que nunca puede quedar
 * inconsistente con sus propias entradas: si `cantidad` o `costo_unitario`
 * se corrigen despues, el total se recalcula solo. Calcularlo en PHP
 * obligaba a acertar en cada INSERT y en cada UPDATE.
 *
 * Esta migracion deja el esquema igual en los dos casos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('global.movimientos_inventario')) {
            return;
        }

        if (!Schema::hasColumn('global.movimientos_inventario', 'costo_total')) {
            DB::statement(
                'ALTER TABLE global.movimientos_inventario
                 ADD COLUMN costo_total numeric(12,2) GENERATED ALWAYS AS (cantidad * costo_unitario) STORED'
            );

            return;
        }

        // Si ya es generada no hay nada que hacer: volver a declararla
        // fallaria, y ademas se perderia el valor historico.
        $yaEsGenerada = DB::selectOne("
            SELECT generation_expression
            FROM information_schema.columns
            WHERE table_schema = 'global'
              AND table_name = 'movimientos_inventario'
              AND column_name = 'costo_total'
              AND is_generated = 'ALWAYS'
        ");

        if ($yaEsGenerada !== null) {
            return;
        }

        DB::statement('ALTER TABLE global.movimientos_inventario DROP COLUMN costo_total');

        DB::statement(
            'ALTER TABLE global.movimientos_inventario
             ADD COLUMN costo_total numeric(12,2) GENERATED ALWAYS AS (cantidad * costo_unitario) STORED'
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('global.movimientos_inventario')
            || !Schema::hasColumn('global.movimientos_inventario', 'costo_total')) {
            return;
        }

        // Se vuelve a una columna normal conservando el valor calculado.
        DB::statement('ALTER TABLE global.movimientos_inventario DROP COLUMN costo_total');

        DB::statement(
            'ALTER TABLE global.movimientos_inventario
             ADD COLUMN costo_total numeric(12,2)'
        );

        DB::statement(
            'UPDATE global.movimientos_inventario
             SET costo_total = COALESCE(cantidad * costo_unitario, 0)'
        );
    }
};