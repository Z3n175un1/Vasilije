<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection('pgsql')->table('global.proveedores')->count() > 0) return;

        DB::connection('pgsql')->table('global.proveedores')->insert([
            ['nit_ci' => '1023456017', 'nombre_proveedor' => 'Llantas del Sur S.R.L.', 'contacto' => 'Carlos Rojas', 'telefono' => '78945612', 'email' => 'ventas@llantassur.bo', 'direccion' => 'Av. Busch N° 123, Santa Cruz', 'tipo_proveedor' => 'LLANTAS'],
            ['nit_ci' => '2034567891', 'nombre_proveedor' => 'Aceites Bolivianos S.A.', 'contacto' => 'María Vaca', 'telefono' => '78945613', 'email' => 'contacto@aceitesbo.bo', 'direccion' => 'Zona Industrial, Cochabamba', 'tipo_proveedor' => 'ACEITES'],
            ['nit_ci' => '3045678912', 'nombre_proveedor' => 'Filtros y Repuestos Ltda.', 'contacto' => 'José Limachi', 'telefono' => '78945614', 'email' => 'ventas@filtros.bo', 'direccion' => 'Av. Petrolera N° 456, La Paz', 'tipo_proveedor' => 'FILTROS'],
            ['nit_ci' => '4056789123', 'nombre_proveedor' => 'Repuestos Originales S.R.L.', 'contacto' => 'Ana Quispe', 'telefono' => '78945615', 'email' => 'contacto@repuestos.bo', 'direccion' => 'Calle 7 N° 890, El Alto', 'tipo_proveedor' => 'REPUESTOS'],
            ['nit_ci' => '5067891234', 'nombre_proveedor' => 'YPFB Transporte S.A.', 'contacto' => 'Juan Pérez', 'telefono' => '78945616', 'email' => 'ventas@ypfb.bo', 'direccion' => 'Av. Grigotá N° 500, Santa Cruz', 'rubro' => 'Combustible', 'tipo_proveedor' => 'COMBUSTIBLE'],
            ['nit_ci' => '6078912345', 'nombre_proveedor' => 'Petrobras Bolivia S.A.', 'contacto' => 'Luís Gómez', 'telefono' => '78945617', 'email' => 'contacto@petrobras.bo', 'direccion' => 'Carretera al Norte Km 7, Cochabamba', 'rubro' => 'Combustible', 'tipo_proveedor' => 'COMBUSTIBLE'],
            ['nit_ci' => '7089123456', 'nombre_proveedor' => 'Diésel Sur S.R.L.', 'contacto' => 'Pedro Morales', 'telefono' => '78945618', 'email' => 'info@diéselsur.bo', 'direccion' => 'Zona Industrial, Santa Cruz', 'rubro' => 'Combustible', 'tipo_proveedor' => 'COMBUSTIBLE'],
            ['nit_ci' => '8091234567', 'nombre_proveedor' => 'Taller Mecánico El Alto', 'contacto' => 'Roberto Quispe', 'telefono' => '78945619', 'email' => 'taller@elalto.bo', 'direccion' => 'Av. La Paz N° 200, El Alto', 'rubro' => 'Mantenimiento', 'tipo_proveedor' => 'TALLER'],
            ['nit_ci' => '9012345678', 'nombre_proveedor' => 'Auto Service Central', 'contacto' => 'Carla Ríos', 'telefono' => '78945620', 'email' => 'service@central.bo', 'direccion' => 'Av. San Martín N° 800, Santa Cruz', 'rubro' => 'Mantenimiento', 'tipo_proveedor' => 'TALLER'],
            ['nit_ci' => '1023456789', 'nombre_proveedor' => 'Seguros Bolivianos S.A.', 'contacto' => 'Marcela Vargas', 'telefono' => '78945621', 'email' => 'contacto@seguros.bo', 'direccion' => 'Calle 21 N° 150, La Paz', 'rubro' => 'Seguros', 'tipo_proveedor' => 'SEGURO'],
            ['nit_ci' => '1123456780', 'nombre_proveedor' => 'Peajes del Oriente S.R.L.', 'contacto' => 'Jorge Llanos', 'telefono' => '78945622', 'email' => 'peajes@oriente.bo', 'direccion' => 'Carretera a Montero Km 15, Santa Cruz', 'rubro' => 'Peajes', 'tipo_proveedor' => 'PEAJE'],
        ]);
    }
}
