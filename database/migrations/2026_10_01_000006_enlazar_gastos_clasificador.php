<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ENLAZA LOS GASTOS HISTORICOS CON SU CLASIFICADOR.
 *
 * Se ejecuta DESPUES de 000005 (siembra del catalogo) y no antes, porque
 * si se enlazara antes habria que crear clasificadores provisionales cuyos
 * codigos CG-0001..CG-000N colisionarian con los definitivos y dejarian el
 * catalogo desplazado.
 *
 * Reglas de resolucion, en orden:
 *   1. Clasificador con el mismo tipo_gasto y el alcance requerido.
 *   2. Si el tipo no existe para ese alcance, cae en "Otros Gastos" (Otro),
 *      que esta marcado como unitario y general: nunca se pierde un gasto.
 *
 * `es_devolucion` ya quedo derivado del signo del monto en 000003.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('global.clasificador_gastos')) {
            return;
        }

        // --- Gastos de UNIDAD ---
        DB::statement(
            'UPDATE global.gastos g
             SET id_clasificador = c.id_clasificador
             FROM global.clasificador_gastos c
             WHERE g.id_clasificador IS NULL
               AND c.tipo_gasto = g.tipo_gasto
               AND c.afecta_unidad = true
               AND c.estado = \'ACTIVO\''
        );

        // --- Gastos GENERALES ---
        DB::statement(
            'UPDATE global.gastos_generales g
             SET id_clasificador = c.id_clasificador
             FROM global.clasificador_gastos c
             WHERE g.id_clasificador IS NULL
               AND c.tipo_gasto = g.categoria
               AND c.afecta_general = true
               AND c.estado = \'ACTIVO\''
        );

        // --- Red de seguridad: clasificador "Otro" ---
        $otroUnidad = DB::table('global.clasificador_gastos')
            ->where('tipo_gasto', 'Otro')->where('afecta_unidad', true)->value('id_clasificador');

        if ($otroUnidad) {
            DB::statement('UPDATE global.gastos SET id_clasificador = ? WHERE id_clasificador IS NULL', [$otroUnidad]);
        }

        $otroGeneral = DB::table('global.clasificador_gastos')
            ->where('tipo_gasto', 'Otro')->where('afecta_general', true)->value('id_clasificador');

        if ($otroGeneral) {
            DB::statement('UPDATE global.gastos_generales SET id_clasificador = ? WHERE id_clasificador IS NULL', [$otroGeneral]);
        }

        // Reporte de cobertura para que la operacion sea auditable.
        $sinClasificador = DB::table('global.gastos')->whereNull('id_clasificador')->count();
        $generalesSinClasificador = DB::table('global.gastos_generales')->whereNull('id_clasificador')->count();

        if ($sinClasificador > 0 || $generalesSinClasificador > 0) {
            \Illuminate\Support\Facades\Log::warning('Gastos sin clasificador tras la normalizacion.', [
                'gastos_unidad' => $sinClasificador,
                'gastos_generales' => $generalesSinClasificador,
            ]);
        }
    }

    public function down(): void
    {
        // El enlace es informacion anadida: revertir la migracion no
        // deshace associations que ademas serian necesarias.
    }
};