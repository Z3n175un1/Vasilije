<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // gastos: condición de pago (CONTADO/CREDITO), método (BANCO/CAJA_CHICA), fecha límite
        DB::statement("ALTER TABLE global.gastos ADD COLUMN IF NOT EXISTS condicion_pago VARCHAR(20) DEFAULT 'CONTADO'");
        DB::statement("ALTER TABLE global.gastos ADD COLUMN IF NOT EXISTS metodo_pago VARCHAR(20)");
        DB::statement("ALTER TABLE global.gastos ADD COLUMN IF NOT EXISTS fecha_limite_pago DATE");

        // movimientos_inventario: condición, método, banco, proveedor, fecha límite
        DB::statement("ALTER TABLE global.movimientos_inventario ADD COLUMN IF NOT EXISTS condicion_pago VARCHAR(20) DEFAULT 'CONTADO'");
        DB::statement("ALTER TABLE global.movimientos_inventario ADD COLUMN IF NOT EXISTS metodo_pago VARCHAR(20)");
        DB::statement("ALTER TABLE global.movimientos_inventario ADD COLUMN IF NOT EXISTS id_banco INTEGER");
        DB::statement("ALTER TABLE global.movimientos_inventario ADD COLUMN IF NOT EXISTS id_proveedor INTEGER");
        DB::statement("ALTER TABLE global.movimientos_inventario ADD COLUMN IF NOT EXISTS fecha_limite_pago DATE");

        // FK banco en gastos (si no existe)
        if (!$this->hasFk('gastos', 'fk_gasto_banco')) {
            DB::statement('ALTER TABLE global.gastos ADD CONSTRAINT fk_gasto_banco FOREIGN KEY (id_banco) REFERENCES global.bancos(id_banco)');
        }
        // FK banco y proveedor en movimientos_inventario
        if (!$this->hasFk('movimientos_inventario', 'fk_mov_banco')) {
            DB::statement('ALTER TABLE global.movimientos_inventario ADD CONSTRAINT fk_mov_banco FOREIGN KEY (id_banco) REFERENCES global.bancos(id_banco)');
        }
        if (!$this->hasFk('movimientos_inventario', 'fk_mov_proveedor')) {
            DB::statement('ALTER TABLE global.movimientos_inventario ADD CONSTRAINT fk_mov_proveedor FOREIGN KEY (id_proveedor) REFERENCES global.proveedores(id_proveedor)');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.movimientos_inventario DROP CONSTRAINT IF EXISTS fk_mov_proveedor');
        DB::statement('ALTER TABLE global.movimientos_inventario DROP CONSTRAINT IF EXISTS fk_mov_banco');
        DB::statement('ALTER TABLE global.gastos DROP CONSTRAINT IF EXISTS fk_gasto_banco');
        DB::statement('ALTER TABLE global.movimientos_inventario DROP COLUMN IF EXISTS fecha_limite_pago');
        DB::statement('ALTER TABLE global.movimientos_inventario DROP COLUMN IF EXISTS id_proveedor');
        DB::statement('ALTER TABLE global.movimientos_inventario DROP COLUMN IF EXISTS id_banco');
        DB::statement('ALTER TABLE global.movimientos_inventario DROP COLUMN IF EXISTS metodo_pago');
        DB::statement('ALTER TABLE global.movimientos_inventario DROP COLUMN IF EXISTS condicion_pago');
        DB::statement('ALTER TABLE global.gastos DROP COLUMN IF EXISTS fecha_limite_pago');
        DB::statement('ALTER TABLE global.gastos DROP COLUMN IF EXISTS metodo_pago');
        DB::statement('ALTER TABLE global.gastos DROP COLUMN IF EXISTS condicion_pago');
    }

    private function hasFk(string $table, string $constraint): bool
    {
        return (bool) DB::selectOne("SELECT 1 FROM information_schema.table_constraints
            WHERE constraint_schema = 'global' AND table_name = '$table'
            AND constraint_name = '$constraint'");
    }
};
