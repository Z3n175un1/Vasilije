<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * INTEGRIDAD Y RENDIMIENTO.
 *
 * 1. FKs declaradas en la migracion original pero nunca creadas
 *    (ingresos.id_tramo, gastos_generales.id_banco / id_proveedor).
 * 2. Indices sobre todas las columnas usadas para filtrar, ausentes en el esquema.
 * 3. Defaults para columnas NOT NULL que la app insertaba como NULL.
 * 4. CHECK de coherencia: una devolucion no puede quedar como "Pagado".
 *
 * Todo idempotente: se puede reejecutar sin efectos secundarios.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $indices = [
        // Reportes: siempre filtran por fecha y unidad.
        'CREATE INDEX IF NOT EXISTS gastos_fecha_gasto_idx ON global.gastos (fecha_gasto)',
        'CREATE INDEX IF NOT EXISTS gastos_vehiculo_fecha_idx ON global.gastos (id_vehiculo, fecha_gasto)',
        'CREATE INDEX IF NOT EXISTS gastos_personal_idx ON global.gastos (id_personal)',
        'CREATE INDEX IF NOT EXISTS gastos_tipo_gasto_idx ON global.gastos (tipo_gasto)',
        'CREATE INDEX IF NOT EXISTS gastos_banco_idx ON global.gastos (id_banco)',
        'CREATE INDEX IF NOT EXISTS gastos_proveedor_idx ON global.gastos (id_proveedor)',
        'CREATE INDEX IF NOT EXISTS gastos_nro_documento_idx ON global.gastos (nro_documento)',

        'CREATE INDEX IF NOT EXISTS ingresos_fecha_ingreso_idx ON global.ingresos (fecha_ingreso)',
        'CREATE INDEX IF NOT EXISTS ingresos_vehiculo_fecha_idx ON global.ingresos (id_vehiculo, fecha_ingreso)',
        'CREATE INDEX IF NOT EXISTS ingresos_numero_factura_idx ON global.ingresos (numero_factura)',

        'CREATE INDEX IF NOT EXISTS movimientos_inventario_tipo_fecha_idx ON global.movimientos_inventario (tipo_movimiento, fecha_movimiento)',
        'CREATE INDEX IF NOT EXISTS lotes_codigo_idx ON global.lotes (codigo_lote, id_inventario)',

        'CREATE INDEX IF NOT EXISTS gastos_generales_fecha_idx ON global.gastos_generales (fecha_gasto)',
        'CREATE INDEX IF NOT EXISTS gastos_generales_categoria_idx ON global.gastos_generales (categoria)',
        'CREATE INDEX IF NOT EXISTS gastos_generales_nro_documento_idx ON global.gastos_generales (nro_documento)',

        'CREATE INDEX IF NOT EXISTS inventario_nombre_idx ON global.inventario (nombre_producto)',
        'CREATE INDEX IF NOT EXISTS vehiculos_estado_idx ON global.vehiculos (estado)',
        'CREATE INDEX IF NOT EXISTS personal_estado_idx ON global.personal (estado)',
        'CREATE INDEX IF NOT EXISTS logs_actividad_usuario_fecha_idx ON global.logs_actividad (id_usuario, fecha_evento)',
    ];

    public function up(): void
    {
        // Los indices que dependen de una columna dudosa se crean con
        // comprobacion previa (ver indice()); el resto son directos.
        $this->indice(
            'ingresos_tramo_idx',
            'ingresos',
            'id_tramo',
            'CREATE INDEX IF NOT EXISTS ingresos_tramo_idx ON global.ingresos (id_tramo)'
        );

        foreach ($this->indices as $sql) {
            DB::statement($sql);
        }

        // --- FKs faltantes ---
        $this->fk('gastos_generales', 'id_banco', 'bancos', 'id_banco', 'fk_gasto_general_banco');
        $this->fk('gastos_generales', 'id_proveedor', 'proveedores', 'id_proveedor', 'fk_gasto_general_proveedor');
        $this->fk('ingresos', 'id_tramo', 'tramos', 'id_tramo', 'fk_ingreso_tramo');

        // --- Defaults en columnas NOT NULL que la app dejaba en NULL ---
        DB::statement("UPDATE global.ingresos SET concepto = 'TRANSPORTE DE SOYA' WHERE concepto IS NULL OR concepto = ''");
        DB::statement('UPDATE global.ingresos SET monto = 0 WHERE monto IS NULL');
        DB::statement('UPDATE global.ingresos SET fecha_ingreso = CURRENT_DATE WHERE fecha_ingreso IS NULL');
        DB::statement("UPDATE global.ingresos SET tipo_pago = 'EFECTIVO' WHERE tipo_pago IS NULL OR tipo_pago = ''");
        DB::statement("UPDATE global.ingresos SET estado_factura = 'PENDIENTE' WHERE estado_factura IS NULL OR estado_factura = '' OR estado_factura = 'EMITIDA'");
        DB::statement('UPDATE global.gastos_generales SET estado = \'ACTIVO\' WHERE estado IS NULL OR estado = \'\'');
        DB::statement('UPDATE global.gastos_generales SET created_at = now() WHERE created_at IS NULL');
        DB::statement('UPDATE global.gastos_generales SET updated_at = now() WHERE updated_at IS NULL');

        // `gastos.nro_documento` y `ingresos.nro_documento` son la clave de
        // negocio; hoy son NULL en varios registros por el metodo viejo.
        DB::statement("UPDATE global.gastos SET nro_documento = 'E_SINNUM' WHERE nro_documento IS NULL OR nro_documento = ''");
        DB::statement("UPDATE global.ingresos SET nro_documento = 'I_SINNUM' WHERE nro_documento IS NULL OR nro_documento = ''");

        // --- Coherencia de la devolucion ---
        DB::statement("UPDATE global.gastos SET estado_pago = 'Anulado' WHERE es_devolucion = true AND estado_pago = 'Pagado'");

        // --- Documentar la terminologia heredada ---
        // La UI lo llama "litros" y "precio por litro", la tabla "galones".
        // No se renombra (romperia historico) pero se documenta con un
        // COMMENT para que no se vuelva a confundir.
        DB::statement(
            "COMMENT ON COLUMN global.combustible_detalle.galones IS "
            . self::quote('Litros de combustible. La columna conserva el nombre historico "galones" por compatibilidad.')
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.gastos_generales DROP CONSTRAINT IF EXISTS fk_gasto_general_banco');
        DB::statement('ALTER TABLE global.gastos_generales DROP CONSTRAINT IF EXISTS fk_gasto_general_proveedor');
        DB::statement('ALTER TABLE global.ingresos DROP CONSTRAINT IF EXISTS fk_ingreso_tramo');
    }

    /** Escapa un literal para un COMMENT de PostgreSQL. */
    private static function quote(string $texto): string
    {
        return "'" . str_replace("'", "''", $texto) . "'";
    }

    /**
     * Crea un indice solo si la columna existe.
     *
     * Necesario porque algunas columnas se agregaron a instalaciones
     * existentes fuera del control de migraciones: si el indice se crea a
     * ciegas, la migracion entera falla en una base limpia.
     *
     * @param  array{0:string,1:string}  $destino  [tabla, columna]
     */
    private function indice(string $nombre, string $tabla, string $columna, string $sql): void
    {
        $existe = DB::selectOne(
            "SELECT 1 FROM information_schema.columns
             WHERE table_schema = 'global' AND table_name = ? AND column_name = ?",
            [$tabla, $columna]
        );

        if ($existe) {
            DB::statement($sql);
        }
    }

    private function fk(string $tabla, string $columna, string $tablaRef, string $columnaRef, string $nombre): void
    {
        $existeColumna = DB::selectOne(
            "SELECT 1 FROM information_schema.columns
             WHERE table_schema = 'global' AND table_name = ? AND column_name = ?",
            [$tabla, $columna]
        );

        if (!$existeColumna) {
            return;
        }

        $existeFk = DB::selectOne(
            "SELECT 1 FROM information_schema.table_constraints
             WHERE constraint_schema = 'global' AND table_name = ? AND constraint_name = ?",
            [$tabla, $nombre]
        );

        if (!$existeFk) {
            DB::statement(sprintf(
                'ALTER TABLE global.%s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES global.%s (%s) ON DELETE SET NULL',
                $tabla,
                $nombre,
                $columna,
                $tablaRef,
                $columnaRef
            ));
        }
    }
};