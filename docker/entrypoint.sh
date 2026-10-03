#!/bin/sh
# =========================================================
# Arranque del contenedor
#
# Que `php artisan serve` es lo UNICO que corre: no se levanta el
# servidor HMR de Vite. Los assets llegan compilados en `public/build` y
# `public/hot` se borra en el build. Si `public/hot` existiera, `@vite`
# apuntaria las etiquetas a un servidor que no existe en la imagen y las
# paginas saldrian en HTML crudo, sin estilos.
#
# Por que el cache de configuracion se arma AQUI y no en el Dockerfile:
# `php artisan config:cache` hornea los valores de `env()` dentro de
# bootstrap/cache/config.php. Durante el build no hay `.env` (esta en
# .dockerignore), asi que hornearia vacios y APP_KEY, DB_PASSWORD y las
# demas quedarian fijadas para siempre: las variables `-e` del despliegue
# no tendrian efecto y el primer request devolveria un 500 por clave de
# cifrado ausente. Aca las variables ya existen, que es cuando tiene
# sentido cachearlas.
# =========================================================

set -e

cd /var/www

# Falla al arrancar, y no en el primer request, si falta lo imprescindible.
if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY no esta definida." >&2
    echo "Pasala con -e APP_KEY=... o en un .env montado." >&2
    exit 1
fi

# `public/hot` manda sobre el manifest de Vite: si llega a existir, la app
# deja de servir los estilos. Se elimina en cada arranque, no solo en el build.
rm -f public/hot

php artisan config:cache --no-ansi

# Las migraciones son cosa del despliegue, no del arranque: aplicarlas aqui
# en cada reinicio haria que un `docker compose up` cambiase el esquema de
# produccion sin que nadie lo pidiera.

exec "$@"