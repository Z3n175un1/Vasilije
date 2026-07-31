<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS global.vista_gastos_vehiculo');
        DB::statement('DROP VIEW IF EXISTS global.vista_resumen_inventario');
        DB::statement('DROP VIEW IF EXISTS global.vista_stock_bajo');

        DB::statement('ALTER TABLE global.vehiculos DROP COLUMN IF EXISTS conductor');
        DB::statement('ALTER TABLE global.gastos DROP COLUMN IF EXISTS proveedor');
        DB::statement('ALTER TABLE global.inventario DROP COLUMN IF EXISTS categoria');

        DB::statement("
            CREATE VIEW global.vista_gastos_vehiculo AS
            SELECT v.id_vehiculo,
                v.placa_vehiculo,
                CONCAT(p.nombres, ' ', p.apellidos) AS conductor,
                g.tipo_gasto,
                COALESCE(SUM(g.monto), 0) AS total_gasto,
                COUNT(g.id_gasto) AS cantidad_gastos,
                MIN(g.fecha_gasto) AS primer_gasto,
                MAX(g.fecha_gasto) AS ultimo_gasto
            FROM global.vehiculos v
            LEFT JOIN global.personal p ON v.id_personal = p.id_personal
            LEFT JOIN global.gastos g ON v.id_vehiculo = g.id_vehiculo
            GROUP BY v.id_vehiculo, v.placa_vehiculo, p.nombres, p.apellidos, g.tipo_gasto
        ");

        DB::statement("
            CREATE VIEW global.vista_resumen_inventario AS
            SELECT CA.nombre AS categoria,
                COUNT(*) AS total_productos,
                COALESCE(SUM(I.stock_actual), 0) AS stock_total,
                COALESCE(SUM(I.stock_actual * I.precio_compra), 0) AS valor_inventario_compra,
                COALESCE(SUM(I.stock_actual * I.precio_venta), 0) AS valor_inventario_venta,
                SUM(CASE WHEN I.stock_actual <= I.stock_minimo THEN 1 ELSE 0 END) AS productos_stock_bajo,
                SUM(CASE WHEN I.stock_actual = 0 THEN 1 ELSE 0 END) AS productos_agotados
            FROM global.inventario I
            LEFT JOIN global.categorias_almacen CA ON I.id_categoria = CA.id_categoria
            WHERE I.estado::text <> 'INACTIVO'::text
            GROUP BY CA.nombre
            ORDER BY CA.nombre
        ");

        DB::statement("
            CREATE VIEW global.vista_stock_bajo AS
            SELECT I.codigo,
                I.nombre_producto,
                CA.nombre AS categoria,
                I.stock_actual,
                I.stock_minimo,
                I.stock_actual - I.stock_minimo AS diferencia,
                I.ubicacion_almacen,
                CASE
                    WHEN I.stock_actual <= 0 THEN 'CRITICO - SIN STOCK'::text
                    WHEN I.stock_actual <= I.stock_minimo THEN 'BAJO - REPONER'::text
                    ELSE 'OK'::text
                END AS nivel_stock
            FROM global.inventario I
            LEFT JOIN global.categorias_almacen CA ON I.id_categoria = CA.id_categoria
            WHERE I.stock_actual <= I.stock_minimo
            ORDER BY I.stock_actual
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.inventario ADD COLUMN categoria varchar(50)');
        DB::statement('ALTER TABLE global.gastos ADD COLUMN proveedor varchar(100)');
        DB::statement('ALTER TABLE global.vehiculos ADD COLUMN conductor varchar(100)');

        DB::statement('DROP VIEW IF EXISTS global.vista_gastos_vehiculo');
        DB::statement('DROP VIEW IF EXISTS global.vista_resumen_inventario');
        DB::statement('DROP VIEW IF EXISTS global.vista_stock_bajo');

        DB::statement("
            CREATE VIEW global.vista_gastos_vehiculo AS
            SELECT v.id_vehiculo, v.placa_vehiculo, v.conductor,
                g.tipo_gasto, SUM(g.monto) AS total_gasto,
                COUNT(g.id_gasto) AS cantidad_gastos,
                MIN(g.fecha_gasto) AS primer_gasto, MAX(g.fecha_gasto) AS ultimo_gasto
            FROM global.vehiculos v
            LEFT JOIN global.gastos g ON v.id_vehiculo = g.id_vehiculo
            GROUP BY v.id_vehiculo, v.placa_vehiculo, v.conductor, g.tipo_gasto
        ");
        DB::statement("
            CREATE VIEW global.vista_resumen_inventario AS
            SELECT categoria, COUNT(*) AS total_productos,
                SUM(stock_actual) AS stock_total,
                SUM(stock_actual * precio_compra) AS valor_inventario_compra,
                SUM(stock_actual * precio_venta) AS valor_inventario_venta,
                SUM(CASE WHEN stock_actual <= stock_minimo THEN 1 ELSE 0 END) AS productos_stock_bajo,
                SUM(CASE WHEN stock_actual = 0 THEN 1 ELSE 0 END) AS productos_agotados
            FROM global.inventario WHERE estado::text <> 'INACTIVO'::text
            GROUP BY categoria ORDER BY categoria
        ");
        DB::statement("
            CREATE VIEW global.vista_stock_bajo AS
            SELECT codigo, nombre_producto, categoria, stock_actual, stock_minimo,
                stock_actual - stock_minimo AS diferencia, ubicacion_almacen,
                CASE WHEN stock_actual <= 0 THEN 'CRITICO - SIN STOCK'::text
                    WHEN stock_actual <= stock_minimo THEN 'BAJO - REPONER'::text
                    ELSE 'OK'::text END AS nivel_stock
            FROM global.inventario WHERE stock_actual <= stock_minimo
            ORDER BY stock_actual
        ");
    }
};
