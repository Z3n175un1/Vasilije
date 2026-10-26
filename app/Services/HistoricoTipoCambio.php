<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * HISTORICO DE TIPO DE CAMBIO OFICIAL, DIA POR DIA.
 *
 * Tabla `global.tipo_cambio_historico` (fecha, tipo_de_cambio). Alimenta
 * la tercera fuente de fletes: registrar cada flete con el cambio oficial
 * que rigio en SU fecha de ingreso, no el vigente hoy.
 *
 * Redes de seguridad:
 *  - Los fines de semana no se registran: el BCB no publica valor nuevo y
 *    el sabado/domingo "heredan" el registro del viernes.
 *  - `valorPara()` devuelve el registro mas reciente anterior o igual a
 *    la fecha pedida (UPDATE-after INSERT semantics del upsert).
 *  - La escritura es best-effort: si la tabla falla, NUNCA tumba la
 *    operacion que originó la consulta al BCB.
 */
final class HistoricoTipoCambio
{
    public const TABLA = 'global.tipo_cambio_historico';

    /**
     * Guarda (o actualiza) el valor oficial de una fecha. Sin fines de
     * semana; una fecha sin BCB no se toca.
     */
    public function registrar(?string $fechaIso, float $valor, int $decimales = 2): void
    {
        if ($fechaIso === null || $valor <= 0) {
            return;
        }

        try {
            $fecha = Carbon::parse($fechaIso)->toDateString();

            if ($this->esFinDeSemana($fecha)) {
                return;
            }

            DB::table(self::TABLA)->updateOrInsert(
                ['fecha' => $fecha],
                [
                    'tipo_de_cambio' => round($valor, $decimales),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } catch (Throwable) {
            // Best-effort: sin historico la operacion sigue.
        }
    }

    /**
     * El valor oficial vigente en una fecha: el registro mas reciente
     * anterior o igual. Sin registro, null (el llamador decide el
     * respaldo: la fecha del flete ANTERIOR a la primera entrada).
     */
    public function valorPara(?string $fechaIso = null): ?float
    {
        $hasta = $fechaIso === null ? now()->toDateString() : Carbon::parse($fechaIso)->toDateString();

        try {
            $fila = DB::table(self::TABLA)
                ->where('fecha', '<=', $hasta)
                ->orderByDesc('fecha')
                ->first(['tipo_de_cambio']);

            return $fila === null ? null : (float) $fila->tipo_de_cambio;
        } catch (Throwable) {
            return null;
        }
    }

    /** Fecha de validez para un flete con fecha X (fin de semana = viernes). */
    public function fechaParam($fechaIso): string
    {
        $fecha = Carbon::parse($fechaIso ?? now()->toDateString());

        // Sabado (6) y domingo (7): el valor que rige es el del viernes.
        if ($fecha->dayOfWeekIso >= 6) {
            $fecha = $fecha->previous(5); // 5 = viernes
        }

        return $fecha->toDateString();
    }

    private function esFinDeSemana(string $fecha): bool
    {
        $iso = Carbon::parse($fecha)->dayOfWeekIso;

        return $iso === 6 || $iso === 7;
    }
}
