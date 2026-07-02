<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection('pgsql')->table('global.usuarios')->count() > 0) return;

        $password = Hash::make('password');

        DB::connection('pgsql')->table('global.usuarios')->insert([
            ['usuario' => 'admin', 'email' => 'admin@transporte.com', 'contrasenha' => Hash::make('admin123'), 'nombres' => 'Administrador', 'apellidos' => 'Sistema', 'rol' => 'admin', 'estado' => 1],
            ['usuario' => 'supervisor', 'email' => 'supervisor@transporte.com', 'contrasenha' => $password, 'nombres' => 'Supervisor', 'apellidos' => 'General', 'rol' => 'supervisor', 'estado' => 1],
            ['usuario' => 'operador', 'email' => 'operador@transporte.com', 'contrasenha' => $password, 'nombres' => 'Operador', 'apellidos' => 'Prueba', 'rol' => 'operador', 'estado' => 1],
            ['usuario' => 'lectura', 'email' => 'lectura@transporte.com', 'contrasenha' => $password, 'nombres' => 'Usuario', 'apellidos' => 'Lectura', 'rol' => 'lectura', 'estado' => 1],
        ]);
    }
}
