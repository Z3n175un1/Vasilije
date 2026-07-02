<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehiculoSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection('pgsql')->table('global.vehiculos')->count() > 0) return;

        DB::connection('pgsql')->table('global.vehiculos')->insert([
            ['placa_vehiculo' => '1234-LMB', 'anho' => 2022, 'tipo_vehiculo' => 'Camión', 'conductor' => 'Juan Mamani', 'tramo_actual' => 'Santa Cruz - La Paz', 'estado' => 1, 'capacidad' => 25, 'kilometraje' => 12800],
            ['placa_vehiculo' => '5678-SCZ', 'anho' => 2023, 'tipo_vehiculo' => 'Camioneta', 'conductor' => 'Carlos Quispe', 'tramo_actual' => 'Santa Cruz - Cochabamba', 'estado' => 1, 'capacidad' => 5, 'kilometraje' => 8500],
            ['placa_vehiculo' => '9012-CBB', 'anho' => 2021, 'tipo_vehiculo' => 'Bus', 'conductor' => 'María Choque', 'tramo_actual' => 'La Paz - Oruro', 'estado' => 1, 'capacidad' => 40, 'kilometraje' => 15200],
            ['placa_vehiculo' => '3456-TJA', 'anho' => 2020, 'tipo_vehiculo' => 'Camión', 'conductor' => 'Pedro Flores', 'tramo_actual' => 'Tarija - Villamontes', 'estado' => 2, 'capacidad' => 20, 'kilometraje' => 9500],
            ['placa_vehiculo' => '7890-PTS', 'anho' => 2024, 'tipo_vehiculo' => 'Camioneta', 'conductor' => 'Ana Vaca', 'tramo_actual' => 'Potosi - Sucre', 'estado' => 1, 'capacidad' => 3, 'kilometraje' => 3200],
        ]);
    }
}
