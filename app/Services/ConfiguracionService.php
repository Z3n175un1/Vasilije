<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Parametros globales del negocio (tabla kv `global.configuracion`).
 *
 * Reemplaza los closures anonimos de routes/web.php que hacian
 * `DB::table('global.configuracion')->pluck(...)` en 6 lugares distintos.
 */
final class ConfiguracionService
{
    public const TIPO_CAMBIO = 'tipo_cambio';
    public const PRECIO_TONELADA = 'precio_tonelada_usd';

    /** @var array<string,string>|null */
    private ?array $cache = null;

    /** @return array<string,string> */
    public function todo(): array
    {
        return $this->cache ??= DB::table('global.configuracion')
            ->pluck('valor', 'llave')
            ->all();
    }

    public function obtener(string $llave, ?string $porDefecto = null): ?string
    {
        return $this->todo()[$llave] ?? $porDefecto;
    }

    public function numero(string $llave, float $porDefecto = 0.0): float
    {
        $valor = $this->obtener($llave);

        return $valor === null || !is_numeric($valor) ? $porDefecto : (float) $valor;
    }

    public function tipoCambio(): float
    {
        return $this->numero(self::TIPO_CAMBIO, 6.96);
    }

    public function precioTonelada(): float
    {
        return $this->numero(self::PRECIO_TONELADA, 13.0);
    }

    public function actualizar(string $llave, string $valor): void
    {
        DB::table('global.configuracion')->where('llave', $llave)->update(['valor' => $valor]);
        $this->cache = null;
    }

    /** @param array<string,string> $valores */
    public function actualizarVarios(array $valores): void
    {
        foreach ($valores as $llave => $valor) {
            $this->actualizar($llave, $valor);
        }
    }

    /** Invalida la memoizacion tras escribir fuera del servicio. */
    public function limpiarCache(): void
    {
        $this->cache = null;
    }
}
