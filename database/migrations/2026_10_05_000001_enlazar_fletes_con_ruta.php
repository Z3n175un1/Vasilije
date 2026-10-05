<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ENLAZA LOS FLETES HISTORICOS CON SU RUTA (`id_tramo`)
 *
 * CONTEXTO
 * --------
 * Los fletes se registran con el campo `id_tramo` en NULL: la columna se
 * acaba de restaurar (`restaurar_ingresos_id_tramo`) y los ingresos anteriores
 * nunca la tuvieron. Hasta ahora los reportes leiamos el texto de
 * `origen`/`destino`.
 *
 * DECISION
 * --------
 * `id_tramo` pasa a ser la referencia oficial de la ruta, pero NO se borra
 * ni se pisa el texto de `origen`/`destino`. El texto es el historico real del
 * flete y se conserva siempre.
 *
 * ESTA MIGRACION NO ADIVINA
 * -------------------------
 * Solo rellena `id_tramo` cuando el par `origen`/`destino` del flete coincide
 * EXACTAMENTE (case y espacios) con el de una ruta del catalogo. Si no
 * coincide, se deja el flete con `id_tramo` NULL a proposito: es preferible
 * un flete sin ruta enlazada que enlazarlo a una ruta equivocada.
 *
 * Casos que quedan sin enlazar, y por que es lo correcto:
 *   - `origen`/`destino` vacios: no hay informacion para deducir la ruta.
 *   - texto que no existe en el catalogo (p. ej. un destino distinto): forzar
 *     el enlace inventaria una relacion que el operador nunca confirmo.
 *
 * Es idempotente y no borra datos: solo escribe `id_tramo` donde estaba NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('global.ingresos') || ! Schema::hasTable('global.tramos')) {
            return;
        }

        if (! Schema::hasColumn('global.ingresos', 'id_tramo')) {
            return;
        }

        $relacionados = 0;

        foreach ($this->rutasUnicas() as $ruta) {
            $relacionados += DB::table('global.ingresos')
                ->whereNull('id_tramo')
                ->where('origen', $ruta->origen)
                ->where('destino', $ruta->destino)
                ->update(['id_tramo' => $ruta->id_tramo]);
        }

        // Dejar rastro en el log: cuantas filas quedaron enlazadas y cuantas
        // quedaron huerfanas. Si el numero de huerfanas no cuadra con lo
        // esperado, hay datos que revisar a mano.
        $huerfanos = DB::table('global.ingresos')
            ->whereNull('id_tramo')
            ->where(function ($q) {
                $q->whereNotNull('origen')->orWhereNotNull('destino');
            })
            ->count();

        fwrite(STDERR, sprintf(
            "\n[backfill id_tramo] %d flete(s) enlazados; %d con texto de ruta sin enlazar (revisar a mano).\n",
            $relacionados,
            $huerfanos
        ));
    }

    public function down(): void
    {
        // No se revierte: `id_tramo` no estaba enlazado antes y los fletes que
        // quedaron con NULL deben seguir con NULL. Borrar el enlace seria
        // perder informacion correcta.
    }

    /**
     * Rutas del catalogo, tal cual estan guardadas.
     *
     * Descarta las que comparten par origen/destino con otra: si dos rutas
     * dicen lo mismo no hay forma de saber cual es la correcta, y elegir una
     * al azar meteria el flete en la ruta equivocada. Esas quedan para que el
     * operador las defina desde la pantalla de Rutas.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function rutasUnicas()
    {
        $duplicadas = DB::table('global.tramos')
            ->whereNotNull('origen')
            ->whereNotNull('destino')
            ->select('origen', 'destino')
            ->groupBy('origen', 'destino')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->map(static fn ($r) => $r->origen . "\0" . $r->destino)
            ->all();

        return DB::table('global.tramos')
            ->whereNotNull('origen')
            ->whereNotNull('destino')
            ->orderBy('id_tramo')
            ->get(['id_tramo', 'origen', 'destino'])
            ->reject(static fn ($ruta) => in_array($ruta->origen . "\0" . $ruta->destino, $duplicadas, true))
            ->values();
    }
};