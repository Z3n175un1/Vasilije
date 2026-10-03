<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a un conjunto de roles o a un nivel minimo.
 *
 * Uso en routes/web.php:
 *
 *   ->middleware('rol:admin')
 *   ->middleware('rol:admin,supervisor')
 *   ->middleware('rol_minimo:supervisor')
 */
final class RolMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            return redirect()->route('login');
        }

        $actual = Rol::normalizar($usuario->rol);

        if (!in_array($actual->value, $roles, true)) {
            abort(403, 'No tiene permisos para acceder a esta seccion.');
        }

        return $next($request);
    }
}
