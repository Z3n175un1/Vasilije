<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige un nivel minimo de permisos, useful para acciones destructivas
 * que several roles podrian ejecutar pero solo/admin deberian confirmar.
 *
 *   ->middleware('nivel:supervisor')
 */
final class NivelMiddleware
{
    public function handle(Request $request, Closure $next, string $rolMinimo): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            return redirect()->route('login');
        }

        $minimo = Rol::normalizar($rolMinimo);
        $actual = Rol::normalizar($usuario->rol);

        if ($actual->nivel() < $minimo->nivel()) {
            abort(403, 'Esta accion requiere permisos de ' . $minimo->label() . '.');
        }

        return $next($request);
    }
}
