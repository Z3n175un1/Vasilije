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
    /** Fuente del valor registrado en fletes: 'MANUAL' o 'BCB' (oficial). */
    public const FUENTE_TIPO_CAMBIO = 'fuente_tipo_cambio';

    private ?BcbService $bcb = null;

    /** @var array<string,string>|null */
    private ?array $cache = null;

    public function __construct(?BcbService $bcb = null)
    {
        $this->bcb = $bcb;
    }

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

    /** ¿El usuario fija el cambio a mano o usa el oficial del BCB? */
    public function fuenteTipoCambio(): string
    {
        return mb_strtoupper($this->obtener(self::FUENTE_TIPO_CAMBIO, 'MANUAL') ?? 'MANUAL');
    }

    /**
     * Tipo de cambio vigente que se USA en los calculos.
     *
     * Con fuente 'BCB' el valor usado es el oficial al dia del Banco Central;
     * si el BCB no responde se recurre al valor manual como respaldo, para
     * que los reportes nunca queden sin divisor dolares->bolivianos.
     */
    public function tipoCambioVigente(): float
    {
        if ($this->fuenteTipoCambio() === 'BCB') {
            $oficial = ($this->bcb ?? new BcbService())->obtener();

            if ($oficial !== null) {
                return $oficial['valor'];
            }
        }

        return $this->tipoCambio();
    }

    public function precioTonelada(): float
    {
        return $this->numero(self::PRECIO_TONELADA, 13.0);
    }

    public function actualizar(string $llave, string $valor): void
    {
        // Upsert: la llave puede no existir todavia (instalacion anterior a
        // la clave nueva). Un UPDATE silencioso guardaria la configuracion
        // sin efecto y el usuario veria "exitoso" sin haberse guardado nada.
        $existe = DB::table('global.configuracion')->where('llave', $llave)->exists();

        if ($existe) {
            DB::table('global.configuracion')->where('llave', $llave)->update(['valor' => $valor]);
        } else {
            DB::table('global.configuracion')->insertOrIgnore([
                'llave' => $llave,
                'valor' => $valor,
                'descripcion' => $llave,
                'created_at' => now(),
            ]);
        }

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
