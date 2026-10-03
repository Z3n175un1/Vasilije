<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Rellena la tabla `global.logs_actividad`, que existia con 11 columnas
 * (usuario, accion, modulo, ip, user_agent, jsonb) pero NUNCA se
 * escribia. Sin esto el sistema no tiene trazabilidad real.
 *
 * La escritura es best-effort: si la auditoria falla nunca debe tumbar
 * la operacion de negocio que la origino.
 */
final class AuditoriaService
{
    /** @var array<int, array{accion:string, modulo:?string, descripcion:?string, datos:?array}> */
    private array $pendientes = [];

    /**
     * @param  array<string, mixed>|null  $datos
     */
    public function registrar(string $accion, ?string $modulo = null, ?string $descripcion = null, ?array $datos = null): void
    {
        $usuario = auth()->user();

        $this->pendientes[] = [
            'accion' => mb_substr($accion, 0, 100),
            'modulo' => $modulo ? mb_substr($modulo, 0, 50) : null,
            'descripcion' => $descripcion,
            'datos' => $datos,
        ];

        // Si estamos en una transaccion, diferimos el volcado al commit.
        if (DB::transactionLevel() === 0) {
            // `getKey()` y no `getAuthIdentifier()`: la columna de
            // identificacion es `usuario`, asi que `getAuthIdentifier()`
            // devuelve el nombre de login (string) y `volcar()` espera el
            // `id_usuario` (int).
            $this->volcar($usuario?->getKey());
        }
    }

    /**
     * Volcar lo pendiente. Se invoca manualmente (p. ej. tras un commit).
     */
    public function volcar(?int $idUsuario = null): void
    {
        if ($this->pendientes === []) {
            return;
        }

        $filas = [];
        $ahora = now();

        foreach ($this->pendientes as $p) {
            $filas[] = [
                'id_usuario' => $idUsuario,
                'usuario' => auth()->user()?->usuario,
                'accion' => $p['accion'],
                'modulo' => $p['modulo'],
                'descripcion' => $p['descripcion'],
                'ip_address' => $this->ip(),
                'user_agent' => mb_substr((string) Request::userAgent(), 0, 500),
                'datos_adicionales' => $p['datos'] ? json_encode($p['datos'], JSON_UNESCAPED_UNICODE) : null,
                'fecha_evento' => $ahora,
            ];
        }

        $this->pendientes = [];

        try {
            DB::table('global.logs_actividad')->insert($filas);
        } catch (Throwable $e) {
            Log::warning('No se pudo escribir el log de actividad.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function ip(): ?string
    {
        try {
            return mb_substr((string) request()->ip(), 0, 45);
        } catch (Throwable) {
            return null;
        }
    }
}
