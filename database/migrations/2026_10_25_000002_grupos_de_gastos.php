<?php

declare(strict_types=1);

use App\Enums\TipoGasto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GRUPOS DE GASTOS (el equivalente de los grupos de almacen, pero para
 * el clasificador).
 *
 * El usuario pide "clasificador de gastos como apartado, que todo no
 * este desplegado": el clasificador vive DENTRO de grupos (p. ej.
 * Telecomunicaciones, Sueldos), se pueden anadir mas grupos y el
 * formulario de gastos permite distinguirlos igual que en almacen
 * distinguimos Grupos de almacen y Grupos de gastos.
 *
 * 1. Tabla `global.grupos_gastos`.
 * 2. Columna `clasificador_gastos.id_grupo_gasto` (FK, SET NULL).
 * 3. Backfill: cada clasificador existente entra al grupo con el nombre
 *    legible de su tipo de gasto, para que la pagina no pierda su
 *    organizacion actual.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('global.grupos_gastos')) {
            DB::statement(
                'CREATE TABLE global.grupos_gastos (
                    id_grupo_gasto  bigserial PRIMARY KEY,
                    nombre          varchar(100) NOT NULL,
                    descripcion     text NULL,
                    created_at      timestamp NULL,
                    updated_at      timestamp NULL
                )'
            );
        }

        DB::statement('ALTER TABLE global.grupos_gastos DROP CONSTRAINT IF EXISTS uq_grupos_gastos_nombre');
        DB::statement('ALTER TABLE global.grupos_gastos ADD CONSTRAINT uq_grupos_gastos_nombre UNIQUE (nombre)');

        if (Schema::hasTable('global.clasificador_gastos')
            && ! Schema::hasColumn('global.clasificador_gastos', 'id_grupo_gasto')) {
            DB::statement(
                'ALTER TABLE global.clasificador_gastos
                 ADD COLUMN id_grupo_gasto bigint NULL
                 REFERENCES global.grupos_gastos (id_grupo_gasto)
                 ON DELETE SET NULL
                 DEFERRABLE INITIALLY DEFERRED'
            );
        }

        DB::statement('CREATE INDEX IF NOT EXISTS clasificador_grupo_gasto_idx ON global.clasificador_gastos (id_grupo_gasto)');
        DB::statement('COMMENT ON COLUMN global.clasificador_gastos.id_grupo_gasto IS \'Grupo de gastos que agrupa este clasificador (telecomunicaciones, sueldos, etc.)\'');

        $this->backfill();
    }

    /**
     * insertOrIgnore + unique de nombre: seguro si la migracion corre dos
     * veces o si el grupo ya existia por una instalacion anterior.
     */
    private function backfill(): void
    {
        if (! Schema::hasTable('global.clasificador_gastos')
            || ! Schema::hasColumn('global.clasificador_gastos', 'id_grupo_gasto')) {
            return;
        }

        // Un grupo por cada tipo de gasto que ya tiene clasificadores:
        // la pagina queda organizada igual que antes, pero por grupo.
        foreach (DB::table('global.clasificador_gastos')->select('tipo_gasto')->distinct()->pluck('tipo_gasto') as $tipo) {
            if (!is_string($tipo) || $tipo === '') {
                continue;
            }

            $nombre = $this->nombreGrupo($tipo);

            DB::table('global.grupos_gastos')->insertOrIgnore([
                'nombre' => $nombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $idGrupo = DB::table('global.grupos_gastos')->where('nombre', $nombre)->value('id_grupo_gasto');

            DB::table('global.clasificador_gastos')
                ->where('tipo_gasto', $tipo)
                ->whereNull('id_grupo_gasto')
                ->update(['id_grupo_gasto' => $idGrupo]);
        }
    }

    /** Nombre legible del grupo para un tipo de gasto ("Telecomunicaciones", etc.). */
    private function nombreGrupo(string $tipo): string
    {
        return TipoGasto::tryFrom($tipo)?->label() ?? $tipo;
    }

    public function down(): void
    {
        if (Schema::hasTable('global.clasificador_gastos')
            && Schema::hasColumn('global.clasificador_gastos', 'id_grupo_gasto')) {
            DB::statement('ALTER TABLE global.clasificador_gastos DROP COLUMN id_grupo_gasto');
        }

        if (Schema::hasTable('global.grupos_gastos')) {
            Schema::dropIfExists('global.grupos_gastos');
        }
    }
};
