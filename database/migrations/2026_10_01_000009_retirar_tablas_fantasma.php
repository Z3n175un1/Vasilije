<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * RETIRA TABLAS FANTASMA.
 *
 * Tablas que existen en el esquema pero que ningun controlador lee ni
 * escribe. Se conservan los datos: solo se retira el objeto, de modo que
 * el `down()` las restaura vacias y el dato historico queda disponible
 * en el dump.
 *
 *  - `sesiones` : la app usa SESSION_DRIVER=file. Esta tabla solo se
 *                 llenaba si alguien cambio el driver a database, y sus
 *                 columnas no coinciden con el payload de Laravel.
 *  - `patrimonio`: se creo en 2026_07_01 y jamas se escribio; el reporte
 *                 financiero estimaba el valor de la flota con un 50000
 *                 fijo por vehiculo en lugar de leerla.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['sesiones', 'patrimonio'] as $tabla) {
            $existe = DB::selectOne(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = 'global' AND table_name = ?",
                [$tabla]
            );

            if ($existe) {
                DB::statement("DROP TABLE IF EXISTS global.{$tabla} CASCADE");
            }
        }
    }

    public function down(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS global.sesiones (
            id bigserial PRIMARY KEY,
            id_usuario integer NULL,
            token varchar(255) NULL,
            agente text NULL,
            ip varchar(45) NULL,
            fecha_inicio timestamp DEFAULT CURRENT_TIMESTAMP,
            fecha_fin timestamp NULL
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS global.patrimonio (
            id_patrimonio bigserial PRIMARY KEY,
            nombre varchar(200) NOT NULL,
            descripcion text NULL,
            valor_estimado numeric(15,2) DEFAULT 0,
            tipo varchar(50) NULL,
            estado integer DEFAULT 1,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP
        )');
    }
};