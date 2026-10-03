<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Condicion de pago de un gasto o compra.
 */
enum CondicionPago: string
{
    case Contado = 'CONTADO';
    case Credito = 'CREDITO';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }

    public function esCredito(): bool
    {
        return $this === self::Credito;
    }

    public function label(): string
    {
        return match ($this) {
            self::Contado => 'CONTADO',
            self::Credito => 'CRÉDITO',
        };
    }
}
