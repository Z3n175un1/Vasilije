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
    /** Fuente del tipo de cambio para fletes: 'BCB' (oficial de hoy) o 'HISTORICO' (por fecha del flete). */
    public const FUENTE_TIPO_CAMBIO = 'fuente_tipo_cambio';

    /** Valor oficial fijo antes del corte del tarifario BCB automatico. */
    public const TC_FIJO = 6.96;

    /** Ultima fecha con el valor fijo 6,96 (a partir de la siguiente rige el historico BCB). */
    public const CUTOFF_TC_AUTO = '2025-09-30';

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

    /** ¿El sistema usa el oficial del BCB de hoy o el de la fecha del flete? */
    public function fuenteTipoCambio(): string
    {
        // Las instalaciones viejas guardaban 'MANUAL' (digitar el valor a
        // mano), opcion ya retirada por causar errores de digitacion: cae a
        // HISTORICO, la fuente que respeta la fecha de cada flete.
        $fuente = $this->obtener(self::FUENTE_TIPO_CAMBIO, 'HISTORICO');

        return in_array(mb_strtoupper($fuente ?? 'HISTORICO'), ['BCB', 'HISTORICO'], true)
            ? mb_strtoupper($fuente ?? 'HISTORICO')
            : 'HISTORICO';
    }

    private ?HistoricoTipoCambio $historico = null;

    private function historico(): HistoricoTipoCambio
    {
        return $this->historico ??= new HistoricoTipoCambio();
    }

    /**
     * Tipo de cambio vigente que se USA en los calculos.
     *
     * Con fuente 'BCB' el valor usado es el oficial al dia del Banco Central;
     * si el BCB no responde se recurre al valor manual como respaldo, para
     * que los reportes nunca queden sin divisor dolares->bolivianos.
     *
     * Con fuente 'HISTORICO' se usa el registro oficial guardado para la
     * fecha dada (tabla tipo_cambio_historico): el valor que rigio cuando
     * se registro el flete. Sin registro para esa fecha recurre al BCB y
     * luego al manual.
     */
    public function tipoCambioVigente(?string $fechaIso = null): float
    {
        if ($this->fuenteTipoCambio() === 'HISTORICO') {
            $historico = $this->historico()->valorPara($fechaIso);

            if ($historico !== null) {
                return $historico;
            }

            $oficial = ($this->bcb ?? new BcbService())->obtener();
            if ($oficial !== null) {
                return $oficial['valor'];
            }
        }

        if ($this->fuenteTipoCambio() === 'BCB') {
            $oficial = ($this->bcb ?? new BcbService())->obtener();

            if ($oficial !== null) {
                return $oficial['valor'];
            }
        }

        return $this->tipoCambio();
    }

    /**
     * Valor que el sistema USA para registrar un flete de la fecha X.
     *
     * REGLA AUTOMATICA, sin digitacion manual:
     *  - Hasta el 30 de septiembre de 2025 (corte del tarifario viejo) el
     *    oficial fijo es 6,96 Bs/USD.
     *  - Fechas posteriores usan el registro oficial del historico BCB de
     *    ESA fecha (sabado/domingo resuelven al viernes). Si la tabla no
     *    tiene valor para esa fecha aun, respaldo 6,96 para que el
     *    registro nunca se bloquee.
     */
    public function tipoCambioParaFecha(?string $fechaIso = null): float
    {
        $fecha = \Illuminate\Support\Carbon::parse($fechaIso ?? now()->toDateString())->toDateString();

        if ($fecha <= self::CUTOFF_TC_AUTO) {
            return self::TC_FIJO;
        }

        return $this->historico()->valorPara($this->historico()->fechaParam($fecha))
            ?? self::TC_FIJO;
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
