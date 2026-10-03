<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Estados del ciclo de facturacion de un flete (ingreso).
 */
enum EstadoFactura: string
{
    case Pendiente = 'PENDIENTE';
    case Facturada = 'FACTURADA';
    case Cobrada = 'COBRADO';
    case Anulada = 'ANULADA';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }

    /** Anulada no suma a los ingresos del periodo. */
    public function sumaAlBalance(): bool
    {
        return $this !== self::Anulada;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'PENDIENTE',
            self::Facturada => 'FACTURADA',
            self::Cobrada => 'COBRADO',
            self::Anulada => 'ANULADA',
        };
    }
}
