<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad aplicadas a toda respuesta web.
 *
 * La app sirve CSS/JS desde CDNs y no tenia ninguna CSP ni proteccion
 * contra clickjacking ni referrer leakage.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        // Evita que la app se incruste en un iframe (clickjacking).
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $respuesta->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // HSTS solo sobre HTTPS.
        if ($request->isSecure()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP: se permite lo que la app realmente usa. 'unsafe-inline' para
        // style/script es necesario porque hay JS y estilos embebidos en Blade;
        // la migracion definitiva a nonces esta planteada en la hoja de ruta.
        $directivas = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.bunny.net",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://fonts.bunny.net data:",
            "img-src 'self' data: blob: https:",
            // Los CDNs sirven sourcemaps; sin esta entrada el navegador los
            // bloquea y la consola se llena de errores que no son reales.
            "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
        ];

        // Con `pnpm dev` los assets los sirve el HMR de Vite, no el manifest.
        // `public/hot` solo existe mientras ese servidor esta levantado, asi
        // que el origen se agrega en desarrollo y desaparece en produccion.
        if ($origenes = $this->origenesDelServidorDeVite()) {
            $permitidos = ' ' . implode(' ', $origenes);

            $directivas[1] .= $permitidos;
            $directivas[2] .= $permitidos;
            $directivas[5] .= $permitidos . ' ws: wss:';
        }

        $respuesta->headers->set('Content-Security-Policy', implode('; ', $directivas));

        return $respuesta;
    }

    /**
     * Orígenes (esquema://host:puerto) del servidor HMR de Vite, vacio si no
     * esta en marcha. Se lee `public/hot`, que Laravel-Vite-plugin escribe al
     * arrancar `pnpm dev` y borra al compilar.
     *
     * @return list<string>
     */
    private function origenesDelServidorDeVite(): array
    {
        // En produccion nunca se permite: aunque un `public/hot` quedara de
        // una sesion de desarrollo anterior, no debe abrir la CSP.
        if (app()->environment('production')) {
            return [];
        }

        $hot = public_path('hot');

        if (! is_file($hot)) {
            return [];
        }

        $url = trim((string) file_get_contents($hot));

        if ($url === '' || ! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return [];
        }

        $partes = parse_url($url);

        if (($partes['host'] ?? null) === null) {
            return [];
        }

        $esquema = $partes['scheme'];
        $puerto = isset($partes['port']) ? ':' . $partes['port'] : '';

        $origenes = [$esquema . '://' . $partes['host'] . $puerto];

        // Vite anuncia `[::1]` y las etiquetas que genera `@vite` usan esa
        // misma URL literal, de modo que el origen IPv6 debe estar en la CSP.
        // Se suma `localhost` porque es como el navegador resuelve la pagina:
        // si se sirve la app por otra via, las dos formas son equivalentes.
        if (in_array($partes['host'], ['[::1]', '::1'], true)) {
            $origenes[] = $esquema . '://localhost' . $puerto;
            $origenes[] = $esquema . '://127.0.0.1' . $puerto;
        }

        return $origenes;
    }
}