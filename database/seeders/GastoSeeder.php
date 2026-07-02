<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GastoSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection('pgsql')->table('global.gastos')->count() > 0) return;

        DB::connection('pgsql')->table('global.gastos')->insert([
            ['id_vehiculo' => 1, 'tipo_gasto' => 'Combustible', 'concepto' => 'Carga de Diesel', 'descripcion' => 'Primera carga del mes', 'monto' => 2625, 'cantidad' => 150, 'fecha_gasto' => '2024-01-15', 'proveedor' => 'YPFB - Santa Cruz', 'kilometraje' => 12500],
            ['id_vehiculo' => 1, 'tipo_gasto' => 'Combustible', 'concepto' => 'Carga de Diesel', 'descripcion' => 'Segunda carga', 'monto' => 3150, 'cantidad' => 180, 'fecha_gasto' => '2024-01-20', 'proveedor' => 'YPFB - La Paz', 'kilometraje' => 12800],
            ['id_vehiculo' => 2, 'tipo_gasto' => 'Combustible', 'concepto' => 'Gasolina Premium', 'descripcion' => 'Carga semanal', 'monto' => 1850, 'cantidad' => 100, 'fecha_gasto' => '2024-01-16', 'proveedor' => 'YPFB - Cochabamba', 'kilometraje' => 8500],
            ['id_vehiculo' => 3, 'tipo_gasto' => 'Combustible', 'concepto' => 'Diesel', 'descripcion' => 'Carga principal', 'monto' => 3500, 'cantidad' => 200, 'fecha_gasto' => '2024-01-18', 'proveedor' => 'YPFB - Oruro', 'kilometraje' => 15200],
            ['id_vehiculo' => 1, 'tipo_gasto' => 'Peaje', 'concepto' => 'Peaje Santa Cruz - La Paz', 'monto' => 35, 'cantidad' => 1, 'fecha_gasto' => '2024-01-15', 'caseta' => 'Caseta Pongo', 'ruta' => 'Santa Cruz - La Paz'],
            ['id_vehiculo' => 1, 'tipo_gasto' => 'Peaje', 'concepto' => 'Peaje La Paz - Santa Cruz', 'monto' => 35, 'cantidad' => 1, 'fecha_gasto' => '2024-01-20', 'caseta' => 'Caseta Caracollo', 'ruta' => 'La Paz - Santa Cruz'],
            ['id_vehiculo' => 2, 'tipo_gasto' => 'Peaje', 'concepto' => 'Peaje Santa Cruz - Cochabamba', 'monto' => 25, 'cantidad' => 1, 'fecha_gasto' => '2024-01-16', 'caseta' => 'Caseta Samaipata', 'ruta' => 'Santa Cruz - Cochabamba'],
            ['id_vehiculo' => 3, 'tipo_gasto' => 'Peaje', 'concepto' => 'Peaje La Paz - Oruro', 'monto' => 30, 'cantidad' => 1, 'fecha_gasto' => '2024-01-18', 'caseta' => 'Caseta El Alto', 'ruta' => 'La Paz - Oruro'],
            ['id_vehiculo' => 1, 'tipo_gasto' => 'Mantenimiento', 'concepto' => 'Cambio de Aceite', 'descripcion' => 'Cambio de aceite y filtros', 'monto' => 650, 'cantidad' => 1, 'fecha_gasto' => '2024-01-15', 'taller' => 'Taller El Progreso', 'tipo_mantenimiento' => 'Cambio de Aceite', 'proveedor' => 'Castrol'],
            ['id_vehiculo' => 1, 'tipo_gasto' => 'Mantenimiento', 'concepto' => 'Alineación', 'descripcion' => 'Alineación y balanceo', 'monto' => 250, 'cantidad' => 1, 'fecha_gasto' => '2024-01-20', 'taller' => 'Lubricentro Central', 'tipo_mantenimiento' => 'Alineación'],
            ['id_vehiculo' => 2, 'tipo_gasto' => 'Mantenimiento', 'concepto' => 'Cambio de Llantas', 'descripcion' => 'Cambio de 2 llantas delanteras', 'monto' => 1800, 'cantidad' => 2, 'fecha_gasto' => '2024-01-16', 'taller' => 'Llantas del Oriente', 'tipo_mantenimiento' => 'Cambio de Llantas', 'proveedor' => 'Goodyear'],
            ['id_vehiculo' => 3, 'tipo_gasto' => 'Mantenimiento', 'concepto' => 'Mantenimiento Preventivo', 'descripcion' => 'Revisión general', 'monto' => 1200, 'cantidad' => 1, 'fecha_gasto' => '2024-01-18', 'taller' => 'Taller Central La Paz', 'tipo_mantenimiento' => 'Preventivo'],
            ['id_vehiculo' => 1, 'tipo_gasto' => 'Administracion', 'concepto' => 'SOAT', 'descripcion' => 'Seguro Obligatorio', 'monto' => 680, 'cantidad' => 1, 'fecha_gasto' => '2024-01-05', 'proveedor' => 'La Boliviana'],
            ['id_vehiculo' => 1, 'tipo_gasto' => 'Administracion', 'concepto' => 'Revisión Técnica', 'descripcion' => 'Revisión técnica vehicular', 'monto' => 280, 'cantidad' => 1, 'fecha_gasto' => '2024-01-10', 'proveedor' => 'CITV Santa Cruz'],
            ['id_vehiculo' => 2, 'tipo_gasto' => 'Administracion', 'concepto' => 'GPS', 'descripcion' => 'Mensualidad GPS', 'monto' => 350, 'cantidad' => 1, 'fecha_gasto' => '2024-01-15', 'proveedor' => 'TrackGPS Bolivia'],
            ['id_vehiculo' => 1, 'tipo_gasto' => 'Varios', 'concepto' => 'Lavado', 'descripcion' => 'Lavado completo', 'monto' => 70, 'cantidad' => 1, 'fecha_gasto' => '2024-01-12', 'proveedor' => 'Lavadero Express'],
            ['id_vehiculo' => 2, 'tipo_gasto' => 'Varios', 'concepto' => 'Herramientas', 'descripcion' => 'Kit de herramientas', 'monto' => 180, 'cantidad' => 1, 'fecha_gasto' => '2024-01-14', 'proveedor' => 'Ferretería Industrial'],
        ]);
    }
}
