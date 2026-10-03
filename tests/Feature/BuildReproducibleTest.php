<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * EL BUILD DE DOCKER TIENE QUE PODER CORRER EN UN CLONE LIMPIO
 *
 * El Dockerfile copia `pnpm-lock.yaml` al contenedor. Si ese archivo no esta
 * versionado, cualquiera que clone el repositorio recibe un error de build
 * antes de compilar nada:
 *
 *     failed to compute cache key: failed to calculate checksum of ref
 *     4ju9ckn5bku77pn2gc13ey1qi::131z8c2kka9rxrwdouxmnp4sh:
 *     "/pnpm-lock.yaml": not found
 *
 * El Dockerfile compila con `--frozen-lockfile`, cuyo unico proposito es
 * fijar versiones exactas. Eso exige el lockfile: sin el, no hay nada que
 * fijar y el flag pierde el sentido.
 */
final class BuildReproducibleTest extends TestCase
{
    #[Test]
    public function el_lockfile_de_pnpm_esta_versionado(): void
    {
        $this->assertContains(
            'pnpm-lock.yaml',
            $this->archivosRastreados(),
            'pnpm-lock.yaml no esta versionado: un clone limpio no podria construir la imagen.'
        );
    }

    #[Test]
    public function el_lockfile_existe_en_disco(): void
    {
        $this->assertFileExists(
            base_path('pnpm-lock.yaml'),
            'pnpm-lock.yaml no esta en el proyecto.'
        );
    }

    #[Test]
    public function el_lockfile_no_esta_ignorado(): void
    {
        // `git check-ignore` devuelve 1 y no imprime nada cuando el archivo
        // NO esta ignorado, que es justo lo que se quiere. Se comprueba el
        // codigo de salida y no la salida, porque con `--cached` el patron
        // se evalua contra el indice y no contra el working tree.
        exec('git check-ignore pnpm-lock.yaml 2>&1', $salida, $codigo);

        $this->assertNotSame(
            0,
            $codigo,
            'pnpm-lock.yaml quedo ignorado: dejara de viajar en el clone. ' . implode(' ', $salida)
        );
    }

    #[Test]
    public function el_package_json_declara_la_version_de_pnpm(): void
    {
        // Sin `packageManager`, corepack resuelve una version de pnpm que
        // puede no coincidir con la del lockfile y `--frozen-lockfile` falla.
        $paquete = json_decode((string) file_get_contents(base_path('package.json')), true);

        $this->assertArrayHasKey(
            'packageManager',
            $paquete,
            'package.json no declara `packageManager`: corepack no sabe que pnpm activar.'
        );

        $this->assertStringStartsWith('pnpm@', $paquete['packageManager']);

        // Y tiene que ser la misma que uso el lockfile.
        $versionDeclarada = substr($paquete['packageManager'], 5);

        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+$/',
            $versionDeclarada,
            "Version de pnpm con formato inesperado: {$versionDeclarada}"
        );
    }

    #[Test]
    public function el_dockerfile_copia_el_lockfile_sin_comodin(): void
    {
        $dockerfile = (string) file_get_contents(base_path('Dockerfile'));

        // Con comodin (`pnpm-lock.yaml*`) el COPY no falla si falta el
        // archivo: el error aparece mas tarde y mas dificil de leer.
        $this->assertStringContainsString(
            'COPY package.json pnpm-lock.yaml ./',
            $dockerfile,
            'El Dockerfile debe copiar el lockfile de forma explicita.'
        );
    }

    #[Test]
    public function el_dockerfile_no_cachea_la_configuracion_en_build(): void
    {
        $dockerfile = (string) file_get_contents(base_path('Dockerfile'));

        // `config:cache` hornea los valores de `env()`. En build no hay .env
        // (esta en .dockerignore), asi que dejaria APP_KEY y DB_PASSWORD
        // vacios para siempre y las variables `-e` del despliegue no
        // tendrian efecto: el primer request devuelve 500.
        $this->assertDoesNotMatchRegularExpression(
            '/artisan\s+config:cache/',
            $dockerfile,
            'config:cache en el build congela las credenciales de despliegue.'
        );

        // Y en su lugar, el arranque.
        $this->assertStringContainsString('config:cache', (string) file_get_contents(base_path('docker/entrypoint.sh')));
    }

    #[Test]
    public function el_entrypoint_existe_y_es_ejecutable(): void
    {
        $ruta = base_path('docker/entrypoint.sh');

        $this->assertFileExists($ruta);

        // Sin el bit de ejecucion, ENTRYPOINT no arranca el script. Se lee el
        // modo del indice de git porque es ahi donde se guarda, y no se usa
        // `cut` porque no existe en Windows.
        $linea = trim((string) shell_exec('git ls-files -s docker/entrypoint.sh 2>&1'));
        $modo = strtok($linea, ' ');

        $this->assertSame(
            '100755',
            $modo,
            'docker/entrypoint.sh no tiene el bit de ejecucion en git.'
        );
    }

    /** @return list<string> */
    private function archivosRastreados(): array
    {
        $salida = (string) shell_exec('git ls-files 2>&1');

        return array_values(array_filter(array_map('trim', explode("\n", $salida))));
    }
}