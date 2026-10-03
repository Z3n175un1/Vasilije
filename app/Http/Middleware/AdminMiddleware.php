<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acceso exclusivo de administrador.
 *
 * Se mantiene el alias `admin` por compatibilidad con las rutas ya
 * escritas, pero ahora la comparacion pasa por el enum Rol en lugar de
 * comparar strings sueltos.
 */
final class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (!$usuario || !Rol::normalizar($usuario->rol)->esAdmin()) {
            if ($request->expectsJson()) {
                abort(403, 'Acceso no autorizado.');
            }

            abort(403, 'Acceso no autorizado.');
        }

        return $next($request);
    }
}
