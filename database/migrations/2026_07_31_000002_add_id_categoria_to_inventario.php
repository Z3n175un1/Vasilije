<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->table('global.inventario', function ($table) {
            $table->integer('id_categoria')->nullable()->after('categoria');
        });

        $db = DB::connection('pgsql');
        $items = $db->table('global.inventario')->select(['id_inventario', 'categoria'])->get();
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

        $db->statement('ALTER TABLE global.inventario ADD CONSTRAINT inventario_id_categoria_foreign FOREIGN KEY (id_categoria) REFERENCES global.categorias_almacen(id_categoria) ON DELETE SET NULL');
    }

    public function down(): void
    {
        Schema::connection('pgsql')->table('global.inventario', function ($table) {
            $table->dropForeign(['id_categoria']);
            $table->dropColumn('id_categoria');
        });
    }
};
