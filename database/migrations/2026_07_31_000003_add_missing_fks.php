<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        DB::statement('ALTER TABLE global.movimientos_inventario ADD CONSTRAINT movimientos_inventario_id_gasto_foreign FOREIGN KEY (id_gasto) REFERENCES global.gastos(id_gasto) ON DELETE SET NULL');
        DB::statement('ALTER TABLE global.movimientos_inventario ADD CONSTRAINT movimientos_inventario_id_vehiculo_foreign FOREIGN KEY (id_vehiculo) REFERENCES global.vehiculos(id_vehiculo) ON DELETE SET NULL');
        DB::statement('ALTER TABLE global.movimientos_inventario ADD CONSTRAINT movimientos_inventario_id_personal_foreign FOREIGN KEY (id_personal) REFERENCES global.personal(id_personal) ON DELETE SET NULL');
        DB::statement('ALTER TABLE global.movimientos_inventario ADD CONSTRAINT movimientos_inventario_registrado_por_foreign FOREIGN KEY (registrado_por) REFERENCES global.usuarios(id_usuario) ON DELETE SET NULL');
        DB::statement('ALTER TABLE global.inventario ADD CONSTRAINT inventario_created_by_foreign FOREIGN KEY (created_by) REFERENCES global.usuarios(id_usuario) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.movimientos_inventario DROP CONSTRAINT IF EXISTS movimientos_inventario_id_gasto_foreign');
        DB::statement('ALTER TABLE global.movimientos_inventario DROP CONSTRAINT IF EXISTS movimientos_inventario_id_vehiculo_foreign');
        DB::statement('ALTER TABLE global.movimientos_inventario DROP CONSTRAINT IF EXISTS movimientos_inventario_id_personal_foreign');
        DB::statement('ALTER TABLE global.movimientos_inventario DROP CONSTRAINT IF EXISTS movimientos_inventario_registrado_por_foreign');
        DB::statement('ALTER TABLE global.inventario DROP CONSTRAINT IF EXISTS inventario_created_by_foreign');
    }
};
