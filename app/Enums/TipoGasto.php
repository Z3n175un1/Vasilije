<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Catalogo canonico de tipos de gasto del sistema.
 *
 * Este enum es la UNICA fuente de verdad del dominio. La columna
 * `gastos.tipo_gasto` y `gastos_generales.tipo_gasto` compartilh
 * este CHECK constraint generado a partir de `values()`.
 *
 * Reglas:
 *  - No anadir variantes en plural ("Sueldos"). El bug historico fue
 *    precisely que PHP validaba "Sueldo" y la BD exigia "Sueldos".
 *  - Los codigos son estables: se persisten en la BD.
 */
enum TipoGasto: string
{
    // --- Afectan a UNIDAD (flota) ---
    case Combustible = 'Combustible';
    case Mantenimiento = 'Mantenimiento';
    case Peaje = 'Peaje';
    case Lubricante = 'Lubricante';
    case Llantas = 'Llantas';
    case Seguro = 'Seguro';
    case Sueldo = 'Sueldo';
    case Viatico = 'Viatico';

    // --- Afectan a la EMPRESA en forma general ---
    case CajaChica = 'CajaChica';
    case ServiciosBasicos = 'ServiciosBasicos';
    case Impuestos = 'Impuestos';
    case Telecomunicaciones = 'Telecomunicaciones';
    case Alquiler = 'Alquiler';
    case Administrativo = 'Administrativo';
    case CompraActivos = 'CompraActivos';

    // --- Transversal ---
    case Varios = 'Varios';
    case Otro = 'Otro';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }

    /**
     * Fragmento de SQL con la lista de valores, para CHECK constraints.
     */
    public static function sqlList(): string
    {
        return implode(', ', array_map(
            static fn (string $v): string => "'" . str_replace("'", "''", $v) . "'",
            self::values()
        ));
    }

    /**
     * Etiqueta legible para la UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::CajaChica => 'Caja Chica',
            self::ServiciosBasicos => 'Servicios Basicos',
            self::CompraActivos => 'Compra de Activos',
            default => $this->value,
        };
    }

    /**
     * Tipos que por naturaleza consumen combustible -> habilitan el
     * sub-formulario de galones / precio por galon.
     */
    public function requiereDetalleCombustible(): bool
    {
        return $this === self::Combustible;
    }

    public function esRemuneracion(): bool
    {
        return in_array($this, [self::Sueldo, self::Viatico], true);
    }

    /**
     * Tipos que, por defecto, solo pueden registrarse contra una unidad.
     */
    public function esOperativoDeUnidad(): bool
    {
        return in_array($this, [
            self::Combustible, self::Mantenimiento, self::Peaje,
            self::Lubricante, self::Llantas, self::Sueldo, self::Viatico,
        ], true);
    }

    /**
     * Tipos por defecto de alcance general (empresa).
     */
    public function esGeneralPorDefecto(): bool
    {
        return in_array($this, [
            self::CajaChica, self::ServiciosBasicos, self::Impuestos,
            self::Telecomunicaciones, self::Alquiler, self::Administrativo,
            self::Varios,
        ], true);
    }

    /**
     * Mapa legacy -> canonico, usado por la migracion de normalizacion.
     *
     * @return array<string, string>
     */
    public static function mapaLegacy(): array
    {
        return [
            'Sueldos' => self::Sueldo->value,
            'Sueldo' => self::Sueldo->value,
            'Viaticos' => self::Viatico->value,
            'Viatico' => self::Viatico->value,
            'Viático' => self::Viatico->value,
            'Vi�tico' => self::Viatico->value,
            'Administracion' => self::Administrativo->value,
            'Administrativo' => self::Administrativo->value,
            'Compra_Activos' => self::CompraActivos->value,
            'CompraActivos' => self::CompraActivos->value,
            'Caja Chica' => self::CajaChica->value,
            'Caja Chiva' => self::CajaChica->value,
            'Servicios Básicos' => self::ServiciosBasicos->value,
            'Servicios Basicos' => self::ServiciosBasicos->value,
            'Servicios B�sicos' => self::ServiciosBasicos->value,
            'ServiciosBásicos' => self::ServiciosBasicos->value,
            'ServiciosBasicos' => self::ServiciosBasicos->value,
            'Telecomunicaciones' => self::Telecomunicaciones->value,
            'Impuestos' => self::Impuestos->value,
            'Alquiler' => self::Alquiler->value,
            'Seguros' => self::Seguro->value,
            'Seguro' => self::Seguro->value,
        ];
    }

    /**
     * Resuelve un valor arbitrario (posiblemente legacy o corrupto) al
     * canonico. Devuelve null si no hay equivalencia.
     */
    public static function normalizar(?string $valor): ?self
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        $valor = trim($valor);

        // Comparacion laxa: ignora acentos, espacios, signos y mayusculas,
        // de modo que 'Compra_Activos', 'compra activos' y 'COMPRAACTIVOS'
        // resuelven al mismo canonico.
        $clave = self::clave($valor);

        foreach (self::mapaLegacy() as $legacy => $canonico) {
            if (self::clave($legacy) === $clave) {
                return self::from($canonico);
            }
        }

        return self::tryFrom($valor);
    }

    /**
     * Reduce un texto a una clave comparable: sin acentos, sin signos y en
     * minusculas. "Servicios Básicos" y "servicios_basicos" -> "serviciosbasicos".
     */
    private static function clave(string $v): string
    {
        $v = mb_strtolower(trim($v));
        $v = strtr($v, ['á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
                         'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
                         'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
                         'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
                         'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
                         'ñ' => 'n', 'ç' => 'c', '�' => '']);

        return preg_replace('/[^a-z]/', '', $v) ?? $v;
    }
}
