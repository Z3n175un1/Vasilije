<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * COMENTARIOS DE DOCUMENTACION SOBRE EL ESQUEMA.
 *
 * El esquema tiene nombres que no se explican solos (diez años de
 * historia). Un COMMENT de PostgreSQL aparece en psql, en pgAdmin y en
 * cualquier herramienta de introspeccion: es documentacion que viaja con
 * la base de datos, a diferencia de un README que se desincroniza.
 *
 * No altera comportamiento; es puramente documental.
 */
return new class extends Migration
{
    public function up(): void
    {
        $comentarios = [
            ['global.clasificador_gastos', 'afecta_unidad',
                'TRUE: el clasificador se ofrece al registrar un gasto contra una UNIDAD de la flota'],
            ['global.clasificador_gastos', 'afecta_general',
                'TRUE: el clasificador se ofrece al registrar un GASTO GENERAL de la empresa'],
            ['global.clasificador_gastos', 'tipo_gasto',
                'Tipo canonico. Debe coincidir con App\\Enums\\TipoGasto::values()'],

            ['global.gastos', 'es_devolucion',
                'TRUE cuando monto < 0. Un monto negativo significa DEVOLUCION, no un gasto negativo'],
            ['global.gastos', 'tipo_gasto',
                'Se deriva del clasificador. Catalogo canonico App\\Enums\\TipoGasto'],
            ['global.gastos', 'tipo_viatico',
                'Solo aplica cuando tipo_gasto = Viatico. Valores: LOCAL, VIAJE'],
            ['global.gastos', 'condicion_pago',
                'CONTADO exige id_banco; CREDITO exige id_proveedor'],
            ['global.gastos', 'estado_pago',
                'Pagado | Pendiente | Anulado. Toda devolucion se fuerza a Anulado'],

            ['global.gastos_generales', 'es_devolucion',
                'TRUE cuando monto < 0 (devolucion de un gasto general)'],
            ['global.gastos_generales', 'categoria',
                'Codigo canonico del tipo (no texto libre). Ver App\\Enums\\TipoGasto'],

            ['global.movimientos_inventario', 'tipo_movimiento',
                'COMPRA suma stock y genera lote; SALIDA lo resta. INGRESO/CONSUMO/ENTRADA son legacy'],
            ['global.movimientos_inventario', 'costo_unitario',
                'Costo por unidad en bolivianos. costo_total = cantidad * costo_unitario'],
            ['global.movimientos_inventario', 'costo_total',
                'Se calcula en InventarioService. Columna existente en produccion, ausente en el historial de migraciones'],

            ['global.lotes', 'cantidad_inicial',
                'Cantidad con la que se creo el lote. No se recalcula en compras posteriores'],
            ['global.lotes', 'cantidad_actual',
                'Saldo vivo del lote. Se recalcula al revertir movimientos'],

            ['global.vehiculos', 'estado',
                '1=Activo, 2=Mantenimiento/Taller, 3=Vendido. Ver App\\Enums\\EstadoVehiculo'],

            ['global.ingresos', 'nro_documento',
                'Numero interno de flete, serie I_. NO es el numero de factura del cliente'],
            ['global.ingresos', 'estado_factura',
                'PENDIENTE | FACTURADA | COBRADO | ANULADA. ANULADA no suma al balance'],
            ['global.ingresos', 'numero_factura',
                'Factura del cliente. Varios fletes pueden compartir numero'],

            ['global.combustible_detalle', 'galones',
                'Litros de combustible (nombre de columna heredado)'],
            ['global.combustible_detalle', 'precio_por_galon',
                'Precio por litro en bolivianos'],

            ['global.bancos', 'saldo_inicial',
                'Saldo de arranque de la cuenta. El saldo actual se DERIVA de los movimientos CONTADO'],
            ['global.proveedores', 'estado',
                '1=Activo, 0=Inactivo. No existe columna saldo_inicial: el saldo se deriva'],

            ['global.configuracion', 'valor',
                'Tabla clave-valor. Llaves usadas: tipo_cambio, precio_tonelada_usd'],

            ['global.logs_actividad', 'datos_adicionales',
                'JSONB con contexto del evento. Llenado por App\\Services\\AuditoriaService'],
        ];

        foreach ($comentarios as [$tabla, $columna, $texto]) {
            DB::statement("COMMENT ON COLUMN {$tabla}.{$columna} IS " . self::quote($texto));
        }

        $tablas = [
            'global.clasificador_gastos' => 'Catalogo maestro de clasificadores de gasto. Reemplaza los <select> con valores literales de cada vista.',
            'global.gastos' => 'Gastos operacionales de la flota. Un registro por unidad, fecha y concepto.',
            'global.gastos_generales' => 'Gastos de la empresa no atribuibles a una unidad: servicios, impuestos, alquiler.',
        ];

        foreach ($tablas as $tabla => $texto) {
            DB::statement("COMMENT ON TABLE {$tabla} IS " . self::quote($texto));
        }
    }

    /** Escapa un literal para un COMMENT de PostgreSQL. */
    private static function quote(string $texto): string
    {
        return "'" . str_replace("'", "''", $texto) . "'";
    }

    public function down(): void
    {
        // Los COMMENT no afectan al comportamiento; se dejan tal cual.
    }
};