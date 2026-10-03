<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SIEMBRA DEL CATALOGO DE CLASIFICADORES DE GASTO.
 *
 * Es idempotente: si el clasificador ya existe (por ejemplo porque la
 * normalizacion lo genero automaticamente) no se duplica.
 *
 * Cada fila declara su alcance:
 *   afecta_unidad  = aparece al registrar un gasto contra una UNIDAD
 *   afecta_general = aparece al registrar un GASTO GENERAL
 */
return new class extends Migration
{
    private const CODIGO_PREFIJO = 'CG-';

    /**
     * [codigo, descripcion, tipo_gasto, afecta_unidad, afecta_general]
     *
     * @var list<array{0:string,1:string,2:string,3:bool,4:bool}>
     */
    private array $catalogo = [
        // --- Operativos de UNIDAD ---
        ['CG-0001', 'Combustible Diesel', 'Combustible', true, false],
        ['CG-0002', 'Combustible Gasolina', 'Combustible', true, false],
        ['CG-0003', 'Combustible GNV', 'Combustible', true, false],
        ['CG-0004', 'Mantenimiento Mecanico', 'Mantenimiento', true, false],
        ['CG-0005', 'Mantenimiento Preventivo', 'Mantenimiento', true, false],
        ['CG-0006', 'Cambio de Neumaticos', 'Llantas', true, false],
        ['CG-0007', 'Reparacion de Neumaticos', 'Llantas', true, false],
        ['CG-0008', 'Lubricantes y Aceites', 'Lubricante', true, false],
        ['CG-0009', 'Peaje en Ruta', 'Peaje', true, false],
        ['CG-0010', 'Seguro de Vehiculo', 'Seguro', true, true],
        ['CG-0011', 'Sueldo de Personal', 'Sueldo', true, false],
        ['CG-0012', 'Viatico de Viaje', 'Viatico', true, false],
        ['CG-0013', 'Viatico Local', 'Viatico', true, false],

        // --- Generales de la EMPRESA ---
        ['CG-0014', 'Caja Chica', 'CajaChica', false, true],
        ['CG-0015', 'Servicios Basicos (Luz/Agua)', 'ServiciosBasicos', false, true],
        ['CG-0016', 'Impuestos y Tributos', 'Impuestos', false, true],
        ['CG-0017', 'Telefonia e Internet', 'Telecomunicaciones', false, true],
        ['CG-0018', 'Alquiler de Instalaciones', 'Alquiler', false, true],
        ['CG-0019', 'Gastos Administrativos', 'Administrativo', false, true],
        ['CG-0020', 'Compra de Activos y Equipamiento', 'CompraActivos', false, true],
        ['CG-0021', 'Gastos Varios', 'Varios', true, true],
        ['CG-0022', 'Otros Gastos', 'Otro', true, true],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('global.clasificador_gastos')) {
            return;
        }

        $ahora = now();

        // Deduplicacion por clave natural (descripcion + tipo + alcance),
        // NO solo por codigo: si una instalacion ya tiene filas, no se deben
        // duplicar clasificadores aunque el codigo no coincida.
        $existentes = DB::table('global.clasificador_gastos')
            ->select(DB::raw('descripcion'), DB::raw('tipo_gasto'), DB::raw('afecta_unidad'), DB::raw('afecta_general'))
            ->get()
            ->map(static fn ($f): string => mb_strtolower($f->descripcion . '|' . $f->tipo_gasto . '|' . (int) $f->afecta_unidad . (int) $f->afecta_general))
            ->all();

        $nuevos = [];
        $codigosUsados = DB::table('global.clasificador_gastos')->pluck('codigo')->all();

        foreach ($this->catalogo as [$codigo, $descripcion, $tipo, $unidad, $general]) {
            $clave = mb_strtolower($descripcion . '|' . $tipo . '|' . (int) $unidad . (int) $general);

            if (in_array($clave, $existentes, true)) {
                continue;
            }

            // Si el codigo ya esta ocupado por otra fila, asignar el siguiente libre.
            if (in_array($codigo, $codigosUsados, true)) {
                $siguiente = count($codigosUsados) + 1;
                do {
                    $codigo = self::CODIGO_PREFIJO . str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
                    $siguiente++;
                } while (in_array($codigo, $codigosUsados, true));
            }

            $codigosUsados[] = $codigo;
            $existentes[] = $clave;

            $nuevos[] = [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'tipo_gasto' => $tipo,
                'afecta_unidad' => $unidad,
                'afecta_general' => $general,
                'estado' => 'ACTIVO',
                'observaciones' => null,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        if ($nuevos !== []) {
            DB::table('global.clasificador_gastos')->insert($nuevos);
        }
    }

    public function down(): void
    {
        $codigos = array_map(static fn (array $f): string => $f[0], $this->catalogo);

        DB::table('global.clasificador_gastos')->whereIn('codigo', $codigos)->delete();
    }
};