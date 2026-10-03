<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * La CSP debe permitir lo que la app carga de verdad: sus propios assets, los
 * CDNs de terceros y, solo en desarrollo, el servidor HMR de Vite. Si se cierra
 * en exceso, el navegador bloquea los estilos y la pagina se ve sin diseno;
 * si se abre en exceso, se pierde la proteccion.
 */
final class SecurityHeadersTest extends TestCase
{
    private function cspDeLaPaginaDeLogin(): string
    {
        return (string) $this->get('/login')
            ->assertOk()
            ->headers->get('Content-Security-Policy');
    }

    public function test_la_csp_permite_los_cdns_que_usa_la_aplicacion(): void
    {
        $csp = $this->cspDeLaPaginaDeLogin();

        $this->assertStringContainsString("https://cdn.jsdelivr.net", $csp, 'Bootstrap, SweetAlert y Chart.js vienen de jsdelivr.');
        $this->assertStringContainsString('https://cdnjs.cloudflare.com', $csp, 'Font Awesome viene de cdnjs.');
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function test_los_sourcemaps_de_los_cdns_no_generan_errores_de_consola(): void
    {
        $this->assertStringContainsString(
            "connect-src 'self' https://cdn.jsdelivr.net",
            $this->cspDeLaPaginaDeLogin(),
            'Sin el CDN en connect-src el navegador bloquea los .map y llena la consola de errores falsos.'
        );
    }

    public function test_en_produccion_no_se_abre_la_csp_al_servidor_de_vite(): void
    {
        $this->app['env'] = 'production';

        // Aunque quedara un `public/hot` de una sesion de desarrollo previa,
        // produccion no debe permitir el origen del HMR.
        $csp = $this->cspDeLaPaginaDeLogin();

        $this->assertStringNotContainsString('5173', $csp);
        $this->assertStringNotContainsString('ws:', $csp);
    }

    public function test_en_desarrollo_se_abre_al_servidor_de_vite_solo_si_existe_hot(): void
    {
        $hot = public_path('hot');
        $existed = is_file($hot);
        $contenidoPrevio = $existed ? file_get_contents($hot) : null;

        try {
            file_put_contents($hot, 'http://[::1]:5173');

            $csp = $this->cspDeLaPaginaDeLogin();

            // El origen literal es el obligatorio: `@vite` genera las
            // etiquetas con la URL tal cual viene en `public/hot`, y la CSP
            // compara contra esa misma URL, no contra `localhost`.
            $this->assertStringContainsString('http://[::1]:5173', $csp);
            $this->assertStringContainsString('http://localhost:5173', $csp);
            $this->assertStringContainsString('ws:', $csp, 'El HMR necesita websocket para recarga en caliente.');
        } finally {
            if ($existed) {
                file_put_contents($hot, (string) $contenidoPrevio);
            } else {
                @unlink($hot);
            }
        }
    }
}
