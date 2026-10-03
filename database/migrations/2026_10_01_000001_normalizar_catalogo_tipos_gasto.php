<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * NORMALIZA EL CATALOGO DE TIPOS DE GASTO.
 *
 * El problema que arregla
 * -----------------------
 * Existian dos vocabularios incompatibles para la misma columna:
 *
 *   - GastoController validaba  in:Combustible,Sueldo,Viatico,...  (singular)
 *   - CHECK en PostgreSQL exigia 'Sueldos','Viaticos',...          (plural)
 *
 * Resultado: registrar un sueldo o un viatico desde la UI fallaba siempre
 * con una violacion de CHECK. Y ReporteController filtraba por los
 * plurales, asi que los gastos validos tampoco aparecian en reportes.
 *
 * Este script:
 *   1. Traduce los valores legacy al catalogo canonico.
 *   2. Reemplaza el CHECK por el catalogo unico.
 *   3. Homogeneiza `gastos_generales.categoria` (que usaba etiquetas
 *      libres con typos: 'Caja Chiva', 'Servicios B{sicos').
 *
 * IMPORTANTE: la lista de valores esta copiada literalmente desde
 * App\Enums\TipoGasto::values(). Una migracion no debe depender de codigo
 * de aplicacion (cambiar el enum no debe reescribir la historia), pero si
 * debe permanecer sincronizada. El test `ClasificadorGastoTest` verifica
 * que ambas listas coincidan.
 */
return new class extends Migration
{
    /** @var array<string,string> legacy => canonico */
    private array $mapa = [
        'Sueldos' => 'Sueldo',
        'Viaticos' => 'Viatico',
        'Administracion' => 'Administrativo',
        'Compra_Activos' => 'CompraActivos',
    ];

    /** @var list<string> catalogo canonico */
    private array $catalogo = [
        'Combustible', 'Mantenimiento', 'Peaje', 'Lubricante', 'Llantas',
        'Seguro', 'Sueldo', 'Viatico', 'CajaChica', 'ServiciosBasicos',
        'Impuestos', 'Telecomunicaciones', 'Alquiler', 'Administrativo',
        'CompraActivos', 'Varios', 'Otro',
    ];

    /** @var array<string,string> etiqueta de gastos_generales => canonico */
    private array $mapaCategorias = [
        'Caja Chica' => 'CajaChica',
        'Caja Chiva' => 'CajaChica',
        'Servicios Básicos' => 'ServiciosBasicos',
        'Servicios Basicos' => 'ServiciosBasicos',
        'Servicios B�sicos' => 'ServiciosBasicos',
        'ServiciosBásicos' => 'ServiciosBasicos',
        'Telecomunicaciones' => 'Telecomunicaciones',
        'Impuestos' => 'Impuestos',
        'Alquiler' => 'Alquiler',
        'Seguros' => 'Seguro',
        'Seguros ' => 'Seguro',
        'Varios' => 'Varios',
    ];

    public function up(): void
    {
        // --- 1. Traducir gastos.tipo_gasto ---
        foreach ($this->mapa as $legacy => $canonico) {
            DB::statement(
                'UPDATE global.gastos SET tipo_gasto = ? WHERE tipo_gasto = ?',
                [$canonico, $legacy]
            );
        }

        // Cualquier valor fuera del catalogo (por ejemplo 'Viǭtico', que era
        // un literal corrupto en el codigo) cae en 'Otro'.
        $permitidos = "'" . implode("', '", $this->catalogo) . "'";
        DB::statement(
            "UPDATE global.gastos SET tipo_gasto = 'Otro'
             WHERE tipo_gasto IS NULL OR tipo_gasto NOT IN ({$permitidos})"
        );

        // --- 2. Reemplazar el CHECK por el catalogo canonico ---
        DB::statement('ALTER TABLE global.gastos DROP CONSTRAINT IF EXISTS gastos_tipo_gasto_check');
        DB::statement(sprintf(
            "ALTER TABLE global.gastos ADD CONSTRAINT gastos_tipo_gasto_check
             CHECK (tipo_gasto IN (%s))",
            $permitidos
        ));

        // --- 3. Homogeneizar gastos_generales.categoria a codigo canonico ---
        foreach ($this->mapaCategorias as $legacy => $canonico) {
            DB::statement(
                'UPDATE global.gastos_generales SET categoria = ? WHERE categoria = ?',
                [$canonico, $legacy]
            );
        }

        DB::statement(
            "UPDATE global.gastos_generales SET categoria = 'Varios'
             WHERE categoria IS NULL OR categoria NOT IN ({$permitidos})"
        );

        DB::statement('ALTER TABLE global.gastos_generales DROP CONSTRAINT IF EXISTS gastos_generales_categoria_check');
        DB::statement(sprintf(
            "ALTER TABLE global.gastos_generales ADD CONSTRAINT gastos_generales_categoria_check
             CHECK (categoria IN (%s))",
            $permitidos
        ));
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE global.gastos DROP CONSTRAINT IF EXISTS gastos_tipo_gasto_check');
        DB::statement("ALTER TABLE global.gastos ADD CONSTRAINT gastos_tipo_gasto_check CHECK (tipo_gasto IN (
            'Combustible','Administracion','Compra_Activos','Varios','Mantenimiento',
            'Peaje','Sueldos','Viaticos','Seguro','Lubricante','Llantas','Otro'))");

        DB::statement('ALTER TABLE global.gastos_generales DROP CONSTRAINT IF EXISTS gastos_generales_categoria_check');

        $permitidos = "'" . implode("', '", $this->catalogo) . "'";
        DB::statement(
            "ALTER TABLE global.gastos_generales ADD CONSTRAINT gastos_generales_categoria_check
             CHECK (categoria IN ({$permitidos}))"
        );
    }
};