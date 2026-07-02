<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventarioSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection('pgsql')->table('global.inventario')->count() > 0) return;

        DB::connection('pgsql')->table('global.inventario')->insert([
            ['codigo' => 'LLA-00001', 'nombre_producto' => 'Llanta 295/80 R22.5', 'descripcion' => 'Llanta radial para camión', 'categoria' => 'Llantas', 'unidad_medida' => 'UNIDAD', 'stock_actual' => 10, 'stock_minimo' => 4, 'stock_maximo' => 20, 'precio_compra' => 850, 'precio_venta' => 950, 'ultimo_costo' => 850, 'ubicacion_almacen' => 'ESTANTE A-1', 'marca' => 'Michelin', 'estado' => 'ACTIVO', 'id_proveedor' => 1],
            ['codigo' => 'LLA-00002', 'nombre_producto' => 'Llanta 11R22.5', 'descripcion' => 'Llanta para camión', 'categoria' => 'Llantas', 'unidad_medida' => 'UNIDAD', 'stock_actual' => 8, 'stock_minimo' => 4, 'stock_maximo' => 15, 'precio_compra' => 780, 'precio_venta' => 880, 'ultimo_costo' => 780, 'ubicacion_almacen' => 'ESTANTE A-2', 'marca' => 'Goodyear', 'estado' => 'ACTIVO', 'id_proveedor' => 1],
            ['codigo' => 'LLA-00003', 'nombre_producto' => 'Llanta 205/55 R16', 'descripcion' => 'Llanta para camioneta', 'categoria' => 'Llantas', 'unidad_medida' => 'UNIDAD', 'stock_actual' => 12, 'stock_minimo' => 6, 'stock_maximo' => 25, 'precio_compra' => 450, 'precio_venta' => 550, 'ultimo_costo' => 450, 'ubicacion_almacen' => 'ESTANTE A-3', 'marca' => 'Bridgestone', 'estado' => 'ACTIVO', 'id_proveedor' => 1],
            ['codigo' => 'ACE-00001', 'nombre_producto' => 'Aceite 15W40', 'descripcion' => 'Aceite mineral para motor diesel', 'categoria' => 'Aceites', 'unidad_medida' => 'GALON', 'stock_actual' => 45, 'stock_minimo' => 10, 'stock_maximo' => 100, 'precio_compra' => 45, 'precio_venta' => 65, 'ultimo_costo' => 45, 'ubicacion_almacen' => 'ESTANTE B-1', 'marca' => 'Castrol', 'estado' => 'ACTIVO', 'id_proveedor' => 2],
            ['codigo' => 'ACE-00002', 'nombre_producto' => 'Aceite 20W50', 'descripcion' => 'Aceite para motor gasolina', 'categoria' => 'Aceites', 'unidad_medida' => 'LITRO', 'stock_actual' => 30, 'stock_minimo' => 15, 'stock_maximo' => 80, 'precio_compra' => 12.50, 'precio_venta' => 18.50, 'ultimo_costo' => 12.50, 'ubicacion_almacen' => 'ESTANTE B-2', 'marca' => 'Mobil', 'estado' => 'ACTIVO', 'id_proveedor' => 2],
            ['codigo' => 'ACE-00003', 'nombre_producto' => 'Aceite de Transmisión', 'descripcion' => 'Aceite para caja de cambios', 'categoria' => 'Aceites', 'unidad_medida' => 'GALON', 'stock_actual' => 20, 'stock_minimo' => 8, 'stock_maximo' => 50, 'precio_compra' => 65, 'precio_venta' => 85, 'ultimo_costo' => 65, 'ubicacion_almacen' => 'ESTANTE B-3', 'marca' => 'Shell', 'estado' => 'ACTIVO', 'id_proveedor' => 2],
            ['codigo' => 'FIL-00001', 'nombre_producto' => 'Filtro de Aceite', 'descripcion' => 'Filtro de aceite para motor', 'categoria' => 'Filtros', 'unidad_medida' => 'UNIDAD', 'stock_actual' => 25, 'stock_minimo' => 8, 'stock_maximo' => 60, 'precio_compra' => 25, 'precio_venta' => 40, 'ultimo_costo' => 25, 'ubicacion_almacen' => 'ESTANTE C-1', 'marca' => 'Fram', 'estado' => 'ACTIVO', 'id_proveedor' => 3],
            ['codigo' => 'FIL-00002', 'nombre_producto' => 'Filtro de Combustible', 'descripcion' => 'Filtro para sistema de combustible', 'categoria' => 'Filtros', 'unidad_medida' => 'UNIDAD', 'stock_actual' => 30, 'stock_minimo' => 10, 'stock_maximo' => 70, 'precio_compra' => 30, 'precio_venta' => 45, 'ultimo_costo' => 30, 'ubicacion_almacen' => 'ESTANTE C-2', 'marca' => 'Mann', 'estado' => 'ACTIVO', 'id_proveedor' => 3],
            ['codigo' => 'FIL-00003', 'nombre_producto' => 'Filtro de Aire', 'descripcion' => 'Filtro para admisión de aire', 'categoria' => 'Filtros', 'unidad_medida' => 'UNIDAD', 'stock_actual' => 18, 'stock_minimo' => 6, 'stock_maximo' => 40, 'precio_compra' => 35, 'precio_venta' => 55, 'ultimo_costo' => 35, 'ubicacion_almacen' => 'ESTANTE C-3', 'marca' => 'Baldwin', 'estado' => 'ACTIVO', 'id_proveedor' => 3],
            ['codigo' => 'REP-00001', 'nombre_producto' => 'Pastillas de Freno', 'descripcion' => 'Juego de pastillas de freno', 'categoria' => 'Repuestos', 'unidad_medida' => 'JUEGO', 'stock_actual' => 10, 'stock_minimo' => 4, 'stock_maximo' => 25, 'precio_compra' => 120, 'precio_venta' => 180, 'ultimo_costo' => 120, 'ubicacion_almacen' => 'ESTANTE D-1', 'marca' => 'Brembo', 'estado' => 'ACTIVO', 'id_proveedor' => 4],
            ['codigo' => 'REP-00002', 'nombre_producto' => 'Kit de Embrague', 'descripcion' => 'Kit completo de embrague', 'categoria' => 'Repuestos', 'unidad_medida' => 'JUEGO', 'stock_actual' => 5, 'stock_minimo' => 2, 'stock_maximo' => 15, 'precio_compra' => 450, 'precio_venta' => 650, 'ultimo_costo' => 450, 'ubicacion_almacen' => 'ESTANTE D-2', 'marca' => 'Valeo', 'estado' => 'ACTIVO', 'id_proveedor' => 4],
            ['codigo' => 'REP-00003', 'nombre_producto' => 'Batería 12V', 'descripcion' => 'Batería para camión', 'categoria' => 'Repuestos', 'unidad_medida' => 'UNIDAD', 'stock_actual' => 8, 'stock_minimo' => 3, 'stock_maximo' => 20, 'precio_compra' => 380, 'precio_venta' => 480, 'ultimo_costo' => 380, 'ubicacion_almacen' => 'ESTANTE D-3', 'marca' => 'Bosch', 'estado' => 'ACTIVO', 'id_proveedor' => 4],
            ['codigo' => 'REP-00004', 'nombre_producto' => 'Amortiguadores', 'descripcion' => 'Juego de amortiguadores', 'categoria' => 'Repuestos', 'unidad_medida' => 'JUEGO', 'stock_actual' => 6, 'stock_minimo' => 3, 'stock_maximo' => 15, 'precio_compra' => 280, 'precio_venta' => 380, 'ultimo_costo' => 280, 'ubicacion_almacen' => 'ESTANTE D-4', 'marca' => 'Monroe', 'estado' => 'ACTIVO', 'id_proveedor' => 4],
        ]);
    }
}
