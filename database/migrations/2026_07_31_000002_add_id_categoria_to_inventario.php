<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * IDEMPOTENTE.
 *
 * `create_inventario_table` ya declara `id_categoria` con su FK. Esta
 * migracion la anadia de nuevo, de modo que sobre una base limpia fallaba
 * con "Duplicate column: id_categoria". Solo funcionaba en la base de
 * desarrollo, donde la tabla se creo antes de que existiera la columna.
 *
 * En ambos casos se conserva el backfill: unos datos con la columna `categoria`
 * (texto) y sin `id_categoria` necesitan quedar enlazados.
 */
return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        $db = DB::connection('pgsql');

        $tieneColumna = Schema::hasColumn('global.inventario', 'id_categoria');

        if (!$tieneColumna) {
            $db->statement('ALTER TABLE global.inventario ADD COLUMN id_categoria integer NULL');
        }

        // Backfill: enlaza el texto de `categoria` con la FK normalizada.
        $tieneCategoria = Schema::hasColumn('global.inventario', 'categoria');

        if ($tieneCategoria) {
            $items = $db->table('global.inventario')
                ->whereNull('id_categoria')
                ->select(['id_inventario', 'categoria'])
                ->get();

            foreach ($items as $item) {
                $cat = $db->table('global.categorias_almacen')
                    ->where('nombre', $item->categoria)
                    ->first();

                if ($cat) {
                    $db->table('global.inventario')
                        ->where('id_inventario', $item->id_inventario)
                        ->update(['id_categoria' => $cat->id_categoria]);
                }
            }
        }

        $existeFk = $db->selectOne(
            "SELECT 1 FROM information_schema.table_constraints
             WHERE constraint_schema = 'global'
               AND table_name = 'inventario'
               AND constraint_name = 'inventario_id_categoria_foreign'"
        );

        if (!$existeFk) {
            $db->statement(
                'ALTER TABLE global.inventario ADD CONSTRAINT inventario_id_categoria_foreign
                 FOREIGN KEY (id_categoria) REFERENCES global.categorias_almacen(id_categoria) ON DELETE SET NULL'
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('global.inventario', 'id_categoria')) {
            Schema::connection('pgsql')->table('global.inventario', function ($table) {
                $table->dropColumn('id_categoria');
            });
        }
    }
};