<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tipo de movimiento de inventario.
 *
 * COMPRA / SALIDA son los unicos que produce la aplicacion.
 * INGRESO / CONSUMO / ENTRADA se conservan por compatibilidad con
 * datos historicos ya cargados.
 */
enum TipoMovimiento: string
{
    case Compra = 'COMPRA';
    case Salida = 'SALIDA';
    case Ingreso = 'INGRESO';
    case Consumo = 'CONSUMO';
    case Entrada = 'ENTRADA';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }

    public static function sqlList(): string
    {
        return implode(', ', array_map(
            static fn (string $v): string => "'" . $v . "'",
            self::values()
        ));
    }

    /** Suma stock. */
    public function sumaStock(): bool
    {
        return in_array($this, [self::Compra, self::Ingreso, self::Entrada], true);
    }

    /** Resta stock. */
    public function restaStock(): bool
    {
        return in_array($this, [self::Salida, self::Consumo], true);
    }

    /** Solo las compras generan lote y actualizan el ultimo costo. */
    public function generaLote(): bool
    {
        return $this === self::Compra;
    }

    public function label(): string
    {
        return match ($this) {
            self::Compra => 'COMPRA',
            self::Salida => 'ENTREGA',
            self::Consumo => 'CONSUMO',
            default => $this->value,
        };
    }
}
