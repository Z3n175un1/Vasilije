<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonalSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection('pgsql')->table('global.personal')->count() > 0) return;

        DB::connection('pgsql')->table('global.personal')->insert([
            ['nombres' => 'Juan', 'apellidos' => 'Mamani Flores', 'cargo' => 'CONDUCTOR', 'telefono' => '78945612', 'licencia' => 'A2B-123456', 'estado' => 1],
            ['nombres' => 'Carlos', 'apellidos' => 'Quispe Vargas', 'cargo' => 'CONDUCTOR', 'telefono' => '78945613', 'licencia' => 'A2B-123457', 'estado' => 1],
            ['nombres' => 'María', 'apellidos' => 'Choque Condori', 'cargo' => 'CONDUCTORA', 'telefono' => '78945614', 'licencia' => 'A2B-123458', 'estado' => 1],
            ['nombres' => 'Pedro', 'apellidos' => 'Flores Paredes', 'cargo' => 'MECANICO', 'telefono' => '78945615', 'licencia' => null, 'estado' => 1],
            ['nombres' => 'Ana', 'apellidos' => 'Vaca García', 'cargo' => 'OPERADOR', 'telefono' => '78945616', 'licencia' => null, 'estado' => 1],
            ['nombres' => 'Roberto', 'apellidos' => 'Condori Mamani', 'cargo' => 'CONDUCTOR', 'telefono' => '78945617', 'licencia' => 'A2B-123459', 'estado' => 1],
            ['nombres' => 'Lucía', 'apellidos' => 'Rojas Jiménez', 'cargo' => 'SUPERVISOR', 'telefono' => '78945618', 'licencia' => null, 'estado' => 1],
            ['nombres' => 'José', 'apellidos' => 'Limachi Céspedes', 'cargo' => 'MECANICO', 'telefono' => '78945619', 'licencia' => null, 'estado' => 1],
            ['nombres' => 'Rosa', 'apellidos' => 'Mamani Quispe', 'cargo' => 'OPERADOR', 'telefono' => '78945620', 'licencia' => null, 'estado' => 1],
            ['nombres' => 'Félix', 'apellidos' => 'Gutiérrez Claure', 'cargo' => 'CONDUCTOR', 'telefono' => '78945621', 'licencia' => 'A2B-123460', 'estado' => 1],
            ['nombres' => 'Patricia', 'apellidos' => 'Sánchez López', 'cargo' => 'ADMINISTRATIVO', 'telefono' => '78945622', 'licencia' => null, 'estado' => 1],
            ['nombres' => 'Miguel', 'apellidos' => 'Torrico Rojas', 'cargo' => 'CONDUCTOR', 'telefono' => '78945623', 'licencia' => 'A2B-123461', 'estado' => 1],
            ['nombres' => 'Claudia', 'apellidos' => 'Fernández Gutiérrez', 'cargo' => 'SUPERVISOR', 'telefono' => '78945624', 'licencia' => null, 'estado' => 1],
            ['nombres' => 'Sergio', 'apellidos' => 'Montaño Villarroel', 'cargo' => 'MECANICO', 'telefono' => '78945625', 'licencia' => null, 'estado' => 1],
            ['nombres' => 'Daniela', 'apellidos' => 'Álvarez Ortiz', 'cargo' => 'OPERADOR', 'telefono' => '78945626', 'licencia' => null, 'estado' => 1],
            ['nombres' => 'Luis', 'apellidos' => 'Rodríguez Zenteno', 'cargo' => 'CONDUCTOR', 'telefono' => '78945627', 'licencia' => 'A2B-123462', 'estado' => 1],
            ['nombres' => 'Verónica', 'apellidos' => 'Castro Rocha', 'cargo' => 'ADMINISTRATIVO', 'telefono' => '78945628', 'licencia' => null, 'estado' => 1],
            ['nombres' => 'Ramiro', 'apellidos' => 'Molina Cuéllar', 'cargo' => 'CONDUCTOR', 'telefono' => '78945629', 'licencia' => 'A2B-123463', 'estado' => 1],
            ['nombres' => 'Silvia', 'apellidos' => 'Vargas Durán', 'cargo' => 'SUPERVISOR', 'telefono' => '78945630', 'licencia' => null, 'estado' => 1],
            ['nombres' => 'Eduardo', 'apellidos' => 'Ortiz Justiniano', 'cargo' => 'MECANICO', 'telefono' => '78945631', 'licencia' => null, 'estado' => 1],
        ]);
    }
}
