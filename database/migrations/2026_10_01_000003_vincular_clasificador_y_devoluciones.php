<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * VINCULA GASTOS Y GASTOS GENERALES CON EL CLASIFICADOR,
 * Y HABILITA EL REGISTRO DE DEVOLUCIONES.
 *
 * columnas nuevas
 * ----------------
 *  gastos.id_clasificador     -> FK al clasificador (alcance = unidad)
 *  gastos.es_devolucion       -> TRUE cuando monto < 0
 *  gastos.creado_por          -> trazabilidad (la tabla ya tiene la FK usuarios)
 *  gastos_generales.id_clasificador -> FK (alcance = general)
 *  gastos_generales.es_devolucion   -> TRUE cuando monto < 0
 *
 * Ademas elimina `gastos.id_clasificacion`, que apuntaba a la tabla
 * `clasificacion` (13 columnas de agregados, nunca escrita) y confonia
 * dos conceptos distintos con nombres casi identicos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------- gastos ----------------
        if (!Schema::hasColumn('global.gastos', 'id_clasificador')) {
            DB::statement('ALTER TABLE global.gastos ADD COLUMN id_clasificador integer NULL');
        }

        if (!Schema::hasColumn('global.gastos', 'es_devolucion')) {
            DB::statement('ALTER TABLE global.gastos ADD COLUMN es_devolucion boolean NOT NULL DEFAULT false');
        }

        if (!Schema::hasColumn('global.gastos', 'creado_por')) {
            DB::statement('ALTER TABLE global.gastos ADD COLUMN creado_por integer NULL');
        }

        // Derivar la devolucion del signo del monto (datos ya existentes).
        DB::statement('UPDATE global.gastos SET es_devolucion = (monto < 0)');

        // Una devolucion es, por definicion, un movimiento contrario al gasto
        // original: queda marcado como "Anulado" para no contarlo como pagado.
        DB::statement("UPDATE global.gastos SET estado_pago = 'Anulado' WHERE monto < 0 AND estado_pago = 'Pagado'");

        // ---------------- gastos_generales ----------------
        if (!Schema::hasColumn('global.gastos_generales', 'id_clasificador')) {
            DB::statement('ALTER TABLE global.gastos_generales ADD COLUMN id_clasificador bigint NULL');
        }

        if (!Schema::hasColumn('global.gastos_generales', 'es_devolucion')) {
            DB::statement('ALTER TABLE global.gastos_generales ADD COLUMN es_devolucion boolean NOT NULL DEFAULT false');
        }

        DB::statement('UPDATE global.gastos_generales SET es_devolucion = (monto < 0)');

        // `categoria` deja de ser texto libre y pasa a ser el codigo canonico
        // del tipo (ver 2026_10_01_000001).
        DB::statement("UPDATE global.gastos_generales SET categoria = 'Varios' WHERE categoria IS NULL OR categoria = ''");

// ---------------- Backfill del clasificador ----------------
        // NO se hace aqui: el catalogo se siembra en 2026_10_01_000005 y el
        // enlace de los gastos historicos se resuelve en 000006. Si se
        // enlazara antes de sembrar, habria que inventar clasificadores
        // provisionales que despues colisionarian con los codigos del catalogo.

        // ---------------- FKs ----------------
        $this->crearFkSiFalta('gastos', 'id_clasificador', 'clasificador_gastos', 'id_clasificador', 'fk_gasto_clasificador');
        $this->crearFkSiFalta('gastos', 'creado_por', 'usuarios', 'id_usuario', 'fk_gasto_creado_por');
        $this->crearFkSiFalta('gastos_generales', 'id_clasificador', 'clasificador_gastos', 'id_clasificador', 'fk_gasto_general_clasificador');

        DB::statement('CREATE INDEX IF NOT EXISTS gastos_id_clasificador_idx ON global.gastos (id_clasificador)');
        DB::statement('CREATE INDEX IF NOT EXISTS gastos_es_devolucion_idx ON global.gastos (es_devolucion)');
        DB::statement('CREATE INDEX IF NOT EXISTS gastos_generales_id_clasificador_idx ON global.gastos_generales (id_clasificador)');

        // ---------------- Retirar la FK legacy ----------------
        // `clasificacion` es una tabla fantasma: se creo en 2026_07_01 y nunca
        // se escribio. Se elimina junto con su FK para dejar de sustentarla.
        if ($this->existeTabla('global.clasificacion')) {
            DB::statement('ALTER TABLE global.gastos DROP CONSTRAINT IF EXISTS gastos_id_clasificacion_fkey');
            DB::statement('DROP TABLE IF EXISTS global.clasificacion CASCADE');
        }

        if (Schema::hasColumn('global.gastos', 'id_clasificacion')) {
            DB::statement('ALTER TABLE global.gastos DROP COLUMN IF EXISTS id_clasificacion');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.gastos DROP CONSTRAINT IF EXISTS fk_gasto_clasificador');
        DB::statement('ALTER TABLE global.gastos DROP CONSTRAINT IF EXISTS fk_gasto_creado_por');
        DB::statement('ALTER TABLE global.gastos_generales DROP CONSTRAINT IF EXISTS fk_gasto_general_clasificador');

        foreach (['id_clasificador', 'es_devolucion', 'creado_por'] as $columna) {
            if (Schema::hasColumn('global.gastos', $columna)) {
                DB::statement('ALTER TABLE global.gastos DROP COLUMN IF EXISTS ' . $columna);
            }
        }

        foreach (['id_clasificador', 'es_devolucion'] as $columna) {
            if (Schema::hasColumn('global.gastos_generales', $columna)) {
                DB::statement('ALTER TABLE global.gastos_generales DROP COLUMN IF EXISTS ' . $columna);
            }
        }
    }

    /**
     * Asocia cada gasto historico al clasificador cuyo tipo coincide.
     * Los clasificadores se siembran en la migracion siguiente; si aun no
     * existen, se crean aqui los minimos por tipo.
     */
    private function backfillClasificadores(): void
    {
        $tipos = DB::table('global.gastos')->distinct()->pluck('tipo_gasto')->all();

        foreach ($tipos as $tipo) {
            if ($tipo === null) {
                continue;
            }

            $existe = DB::table('global.clasificador_gastos')
                ->where('tipo_gasto', $tipo)
                ->where('afecta_unidad', true)
                ->exists();

            if (!$existe) {
                DB::table('global.clasificador_gastos')->insert([
                    'codigo' => 'CG-' . str_pad((string) (DB::table('global.clasificador_gastos')->count() + 1), 4, '0', STR_PAD_LEFT),
                    'descripcion' => $tipo,
                    'tipo_gasto' => $tipo,
                    'afecta_unidad' => true,
                    'afecta_general' => false,
                    'estado' => 'ACTIVO',
                    'observaciones' => 'Generado automaticamente durante la normalizacion del catalogo.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Un clasificador de alcance general debe existir antes del backfill
        // de gastos_generales.
        $generales = DB::table('global.gastos_generales')->distinct()->pluck('categoria')->all();

        foreach ($generales as $categoria) {
            if ($categoria === null || $categoria === '') {
                continue;
            }

            $existe = DB::table('global.clasificador_gastos')
                ->where('tipo_gasto', $categoria)
                ->where('afecta_general', true)
                ->exists();

            if (!$existe) {
                DB::table('global.clasificador_gastos')->insert([
                    'codigo' => 'CG-' . str_pad((string) (DB::table('global.clasificador_gastos')->count() + 1), 4, '0', STR_PAD_LEFT),
                    'descripcion' => $categoria,
                    'tipo_gasto' => $categoria,
                    'afecta_unidad' => false,
                    'afecta_general' => true,
                    'estado' => 'ACTIVO',
                    'observaciones' => 'Generado automaticamente durante la normalizacion del catalogo.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::statement(
            'UPDATE global.gastos g
             SET id_clasificador = c.id_clasificador
             FROM global.clasificador_gastos c
             WHERE g.id_clasificador IS NULL
               AND c.tipo_gasto = g.tipo_gasto
               AND c.afecta_unidad = true'
        );

        DB::statement(
            'UPDATE global.gastos_generales g
             SET id_clasificador = c.id_clasificador
             FROM global.clasificador_gastos c
             WHERE g.id_clasificador IS NULL
               AND c.tipo_gasto = g.categoria
               AND c.afecta_general = true'
        );
    }

    private function crearFkSiFalta(string $tabla, string $columna, string $tablaRef, string $columnaRef, string $nombre): void
    {
        $existe = DB::selectOne(
            "SELECT 1 FROM information_schema.table_constraints
             WHERE constraint_schema = 'global' AND table_name = ? AND constraint_name = ?",
            [$tabla, $nombre]
        );

        if (!$existe) {
            DB::statement(sprintf(
                'ALTER TABLE global.%s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES global.%s (%s) ON DELETE SET NULL',
                $tabla,
                $nombre,
                $columna,
                $tablaRef,
                $columnaRef
            ));
        }
    }

    private function existeTabla(string $tabla): bool
    {
        return (bool) DB::selectOne(
            "SELECT 1 FROM information_schema.tables WHERE table_schema = 'global' AND table_name = ?",
            [explode('.', $tabla)[1]]
        );
    }
};