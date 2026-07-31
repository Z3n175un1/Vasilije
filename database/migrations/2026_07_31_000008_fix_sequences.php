<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        $max = DB::connection('pgsql')->table('global.inventario')->max('id_inventario');
        if ($max) {
            DB::statement("SELECT setval('global.inventario_id_inventario_seq', {$max})");
        }

        $tables = [
            'gastos' => 'id_gasto', 'ingresos' => 'id_ingreso', 'vehiculos' => 'id_vehiculo',
            'personal' => 'id_personal', 'proveedores' => 'id_proveedor', 'bancos' => 'id_banco',
            'lotes' => 'id_lote', 'movimientos_inventario' => 'id_movimiento',
            'categorias_almacen' => 'id_categoria', 'clasificacion' => 'id_clasificacion',
            'combustible_detalle' => 'id_detalle', 'sesiones' => 'id_sesion',
            'tramos' => 'id_tramo', 'usuarios' => 'id_usuario', 'logs_actividad' => 'id_log',
            'patrimonio' => 'id_patrimonio', 'configuracion' => 'id_configuracion',
        ];
        foreach ($tables as $t => $pk) {
            $maxId = DB::connection('pgsql')->table("global.{$t}")->max($pk);
            if ($maxId) {
                DB::statement("SELECT setval('global.{$t}_{$pk}_seq', {$maxId})");
            }
        }
    }

    public function down(): void
    {
    }
};
