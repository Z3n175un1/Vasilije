<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProveedorSeeder::class,
            PersonalSeeder::class,
            UsuarioSeeder::class,
            VehiculoSeeder::class,
            InventarioSeeder::class,
            GastoSeeder::class,
        ]);
    }
}
