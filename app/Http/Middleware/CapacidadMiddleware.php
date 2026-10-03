<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autorizacion por CAPACIDAD de negocio, no por rol.
 *
 * Es la pieza que faltaba en el sistema: existian cuatro roles definidos
 * pero solo `admin` estaba protegido. Un usuario con rol `lectura` podia
 * crear y borrar gastos, fletes, bancos y proveedores porque ningun
 * middleware o policy lo comprobaba.
 *
 * Se registran como alias:
 *
 *   lectura    -> ver
 *   escritura  -> crear | editar
 *   borrado    -> eliminar
 *   anular     -> anular
 *   administrar-> administrar
 *
 * La matriz real vive en App\Policies\ModuloPolicy.
 */
final class CapacidadMiddleware
{
    /**
     * Alias de middleware => capacidades admitidas (basta con una).
     *
     * @var array<string, list<string>>
     */
    private const ALIAS = [
        'lectura' => ['ver'],
        'escritura' => ['crear', 'editar'],
        'borrado' => ['eliminar'],
        'anular' => ['anular'],
        'administrar' => ['administrar'],
    ];

    public function handle(Request $request, Closure $next, string $alias, ?string $modulo = null): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            return redirect()->route('login');
        }

        $capacidades = self::ALIAS[$alias] ?? [$alias];

        // El modulo se puede fijar por parametro de ruta, p. ej.
        // ->middleware('escritura:gastos'); si no, se deduce del nombre de
        // la ruta para no tener que repetirlo en cada declaracion.
        $modulo ??= $this->deducirModulo($request);

        foreach ($capacidades as $capacidad) {
            if ($usuario->can($capacidad, $modulo)) {
                return $next($request);
            }
        }

        abort(403, 'Su rol no permite realizar esta accion.');
    }

    private function deducirModulo(Request $request): string
    {
        $accion = $request->route()?->getActionMethod() ?? '';
        $nombre = $request->route()?->getName() ?? '';

        // `gastos-generales.store` -> gastos_generales
        if (str_contains($nombre, '.')) {
            $segmento = explode('.', $nombre)[0];

            return str_replace('-', '_', $segmento);
        }

        // Fallback por metodo HTTP sobre la primera segmento de la URI.
        $segmento = explode('/', trim($request->path(), '/'))[0] ?? 'sistema';

        return str_replace('-', '_', $segmento);
    }
}