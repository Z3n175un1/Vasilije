<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * NINGUNA CREDENCIAL LLEGA AL REPOSITORIO
 *
 * El repo es publico y se comparte. Un `.env` versionado expone el host, el
 * usuario y la contrasena de la base de produccion, y con eso se accede a los
 * 619 ingresos y a los datos personales de los proveedores.
 *
 * Estos tests fallan si eso llega a colarse, para que el error aparezca en el
 * pipeline y no un martes a las tres de la manana.
 */
final class SinCredencialesEnGitTest extends TestCase
{
    /**
     * Archivos que nunca deben estar versionados: llevan credenciales.
     *
     * @return list<array{0:string}>
     */
    public static function rutasProhibidas(): array
    {
        return [
            ['.env'],
            ['.env.testing'],
            ['.env.produccion'],
            ['.env.local'],
            ['/auth.json'],
            ['database/database.sqlite'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('rutasProhibidas')]
    public function el_archivo_no_esta_versionado(string $ruta): void
    {
        $this->assertNotTracked($ruta);
    }

    #[Test]
    public function ningun_archivo_de_entorno_esta_versionado(): void
    {
        // `.env.*` se ignora entero salvo `.env.example`. Si alguien crea un
        // `.env.staging` con claves, este test lo detecta aunque no este en
        // la lista de arriba.
        $entornos = array_filter(
            $this->archivosRastreados(),
            static fn (string $ruta): bool => preg_match('/(^|\/)\.env/', $ruta) === 1
        );

        $permitidos = ['.env.example'];

        $filtrados = array_values(array_filter(
            $entornos,
            static fn (string $r): bool => !in_array($r, $permitidos, true)
        ));

        $this->assertSame(
            [],
            $filtrados,
            "Archivos de entorno con posibles credenciales versionados: " . implode(', ', $filtrados)
        );
    }

    #[Test]
    public function el_archivo_de_entorno_por_defecto_esta_ignorado(): void
    {
        // Si `.env` saliera del `.gitignore`, el test anterior seguiria en
        // verde hasta que alguien hiciera `git add`. Esto avisa antes.
        $this->assertNotSame(
            '',
            (string) shell_exec('git check-ignore .env 2>&1'),
            '`.env` dejo de estar en el .gitignore'
        );
    }

    #[Test]
    public function el_respaldo_de_la_base_no_se_versiona(): void
    {
        // Un `pg_dump` contiene todos los registros de la empresa.
        $respaldos = array_filter(
            $this->archivosRastreados(),
            static fn (string $r): bool => str_contains($r, 'respaldos/') && str_ends_with($r, '.sql')
        );

        $this->assertSame(
            [],
            array_values($respaldos),
            'Hay respaldos de base de datos versionados: ' . implode(', ', $respaldos)
        );
    }

    #[Test]
    public function el_plantilla_de_entorno_no_contiene_credenciales(): void
    {
        // `.env.example` si se versiona, pero jamas con claves reales.
        $ejemplo = base_path('.env.example');

        if (! is_file($ejemplo)) {
            $this->markTestSkipped('No hay .env.example en el proyecto.');
        }

        $contenido = (string) file_get_contents($ejemplo);

        foreach (explode("\n", $contenido) as $numero => $linea) {
            $linea = trim($linea);

            // Solo las asignaciones activas importan: lo comentado es documentacion.
            if ($linea === '' || str_starts_with($linea, '#') || ! str_contains($linea, '=')) {
                continue;
            }

            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));

            if ($valor === '' || str_starts_with($valor, 'changeme') || str_starts_with($valor, '${')) {
                continue;
            }

            $esSecreto = (bool) preg_match('/(PASSWORD|SECRET|TOKEN|KEY|SUPABASE_URL)/i', $clave);

            $this->assertFalse(
                $esSecreto && strlen($valor) > 8,
                ".env.example linea " . ($numero + 1) . ": `$clave` tiene un valor con pinta de secreto."
            );
        }
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /** @return list<string> */
    private function archivosRastreados(): array
    {
        $salida = (string) shell_exec('git ls-files 2>&1');

        return array_values(array_filter(array_map('trim', explode("\n", $salida))));
    }

    private function assertNotTracked(string $ruta): void
    {
        $this->assertNotContains(
            ltrim($ruta, '/'),
            $this->archivosRastreados(),
            "`{$ruta}` esta versionado: puede contener credenciales."
        );
    }
}