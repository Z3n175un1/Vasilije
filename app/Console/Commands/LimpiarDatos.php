<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimpiarDatos extends Command
{
    protected $signature = 'limpiar:datos {--force : Ejecutar sin confirmacion}';
    protected $description = 'Elimina todos los datos de vehiculos, personal, inventario, items, grupos, bancos, tramos';

    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Se eliminaran TODOS los datos de vehiculos, personal, inventario, categorias, bancos y tramos. Continuar?')) {
                $this->info('Cancelado');
                return;
            }
        }

        $this->info('Eliminando datos...');

        DB::statement('TRUNCATE TABLE global.personal, global.vehiculos, global.inventario, global.categorias_almacen, global.bancos, global.tramos CASCADE');

        $this->info('Datos eliminados correctamente');
    }
}
