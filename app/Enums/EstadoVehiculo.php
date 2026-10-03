<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Estado del ciclo de vida de una unidad (flota).
 * Sustituye al entero magico disperso por el codigo.
 */
enum EstadoVehiculo: int
{
    case Activo = 1;
    case Taller = 2;
    case Vendido = 3;

    /** @return list<int> */
    public static function values(): array
    {
        return array_map(static fn (self $c): int => $c->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Activo => 'ACTIVO',
            self::Taller => 'MANTENIMIENTO',
            self::Vendido => 'VENDIDO',
        };
    }

    /** Unidades operativas: admiten ingresos y gastos. */
    public function esOperativo(): bool
    {
        return $this === self::Activo || $this === self::Taller;
    }

    public static function normalizar(int|string|null $valor): self
    {
        return is_numeric($valor) ? (self::tryFrom((int) $valor) ?? self::Activo) : self::Activo;
    }
}
