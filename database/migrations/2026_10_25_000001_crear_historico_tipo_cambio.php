<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HISTORICO DE TIPO DE CAMBIO OFICIAL (por dia).
 *
 * Cada vez que el sistema obtiene el valor oficial del BCB lo guarda con
 * su fecha de validez: fecha | tipo_de_cambio. Los fines de semana no se
 * registran (el mercado no publica) y la fecha se queda con el registro
 * del viernes.
 *
 * Sirve para la tercera fuente de fletes: registrar cada flete con el
 * cambio oficial que rigio en SU fecha, aunque hoy valga otra cosa.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('global.tipo_cambio_historico')) {
            DB::statement(
                'CREATE TABLE global.tipo_cambio_historico (
                    id_historico   bigserial PRIMARY KEY,
                    fecha          date NOT NULL,
                    tipo_de_cambio numeric(15,2) NOT NULL,
                    created_at     timestamp NULL,
                    updated_at     timestamp NULL
                )'
            );
        }

        // Un nombre fijo + DROP IF EXISTS es el patron idempotente del repo.
        DB::statement('ALTER TABLE global.tipo_cambio_historico DROP CONSTRAINT IF EXISTS uq_tipo_cambio_historico_fecha');
        DB::statement('ALTER TABLE global.tipo_cambio_historico ADD CONSTRAINT uq_tipo_cambio_historico_fecha UNIQUE (fecha)');

        DB::statement('COMMENT ON COLUMN global.tipo_cambio_historico.tipo_de_cambio IS \'Bs por USD publicado por el BCB con validez en `fecha`\'');
    }

    public function down(): void
    {
        if (! Schema::hasTable('global.tipo_cambio_historico')) {
            return;
        }

        Schema::dropIfExists('global.tipo_cambio_historico');
    }
};
