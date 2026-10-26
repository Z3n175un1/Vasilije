<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * TIPO DE CAMBIO OFICIAL DEL BANCO CENTRAL DE BOLIVIA.
 *
 * Puerta en PHP de la API que el usuario construyo en app/Http/Api/api.py:
 * consulta https://www.bcb.gob.bo/, lee el valor del widget `bcb-tco-num`
 * (por ejemplo 11,73) y la fecha de validez que aparece en la misma tarjeta.
 *
 * Un cache de 20 minutos evita bombardear la pagina del BCB desde el
 * dashboard y desde la configuracion.
 */
final class BcbService
{
    private const URL = 'https://www.bcb.gob.bo/';
    private const CACHE_KEY = 'bcb_tipo_cambio';
    private const TTL_MINUTOS = 20;

    /** @var list<string> */
    private const MESES = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];

    /** @var list<string> */
    private const DIAS = [
        'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo',
    ];

    /**
     * Tipo de cambio oficial vigente con cache.
     *
     * @return array{valor: float, valor_texto: string, fecha: string, fecha_iso: string}|null
     */
    public function obtener(bool $forzar = false): ?array
    {
        if (!$forzar) {
            /** @var array|null $cache */
            $cache = cache()->get(self::CACHE_KEY);
            if ($cache !== null) {
                return $cache;
            }
        }

        $datos = $this->consultar();
        if ($datos === null) {
            return null;
        }

        cache()->put(self::CACHE_KEY, $datos, now()->addMinutes(self::TTL_MINUTOS));

        // Registro diario del valor oficial (fecha | tipo_de_cambio): es
        // best-effort y no tumba la consulta si la tabla falla.
        app(HistoricoTipoCambio::class)->registrar($datos['fecha_iso'], $datos['valor']);

        return $datos;
    }

    /** @return array{valor: float, valor_texto: string, fecha: string, fecha_iso: string}|null */
    private function consultar(): ?array
    {
        try {
            $respuesta = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (DS Transporte SRL; tipo de cambio BCB)'])
                ->retry(2, 300)
                ->get(self::URL);
        } catch (\Throwable) {
            return null;
        }

        if (!$respuesta->successful()) {
            return null;
        }

        return $this->parsear($respuesta->body());
    }

    /**
     * Parseo puro: separado de la consulta para poder probarlo sin red.
     *
     * HTML de referencia (octubre 2026):
     *   <div class="bcb-tco-amount">
     *     <span class="bcb-tco-num">11,73</span>
     *   </div>
     * ... y en la misma tarjeta la fecha al dia.
     *
     * @return array{valor: float, valor_texto: string, fecha: string, fecha_iso: string}|null
     */
    public function parsear(string $html): ?array
    {
        // El widget esta en la parte visible; la regla CSS con el mismo nombre
        // no trae texto, asi que solo importan los <span class="bcb-tco-num">.
        if (!preg_match('/<span\s+class="bcb-tco-num">\s*([\d.,]+)\s*<\/span>/u', $html, $match)) {
            return null;
        }

        $texto = trim($match[1]);
        if ($texto === '') {
            return null;
        }

        // Bolivia usa coma decimal ("11,73") pero se admite punto.
        $valor = (float) str_replace([' ', "\u{00a0}", ','], ['', '', '.'], $texto);
        if ($valor <= 0) {
            return null;
        }

        $fechaIso = $this->fechaDeValidez($html);

        return [
            'valor' => $valor,
            'valor_texto' => $texto,
            'fecha' => $this->fechaEnTexto($fechaIso),
            'fecha_iso' => $fechaIso,
        ];
    }

    /** Busca la fecha de vigencia; sin acierto devuelve hoy. */
    private function fechaDeValidez(string $html): string
    {
        $meses = implode('|', self::MESES);

        if (preg_match('/(\d{1,2})\s+de\s+(' . $meses . '),?\s+(\d{4})/u', strtolower(html_entity_decode($html)), $m)) {
            $mes = array_search($m[2], self::MESES, true);

            return sprintf('%04d-%02d-%02d', (int) $m[3], $mes === false ? 1 : ($mes + 1), (int) $m[1]);
        }

        return now()->toDateString();
    }

    private function fechaEnTexto(string $fechaIso): string
    {
        $partes = \Illuminate\Support\Carbon::parse($fechaIso);

        return sprintf(
            '%s %d de %s de %d',
            self::DIAS[$partes->dayOfWeekIso - 1],
            $partes->day,
            self::MESES[$partes->month - 1],
            $partes->year,
        );
    }
}
