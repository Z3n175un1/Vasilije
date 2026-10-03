# syntax=docker/dockerfile:1
# =========================================================
# ETAPA 1: FRONTEND (VITE)
#
# Se compila en build time y el resultado se copia a la etapa final.
# En la imagen final NO se corre `pnpm dev`: ese servidor escribe
# `public/hot`, y mientras ese archivo exista, `@vite` genera las
# etiquetas apuntando al HMR en vez de al manifest. En un contenedor
# eso deja las paginas sin CSS y en HTML crudo, que es exactamente lo
# que paso antes de este cambio.
# =========================================================

FROM node:22-alpine AS frontend

WORKDIR /app

RUN corepack enable

# Se copian primero los manifiestos para que la capa de dependencias
# se reutilice mientras no cambien las versiones.
COPY package.json pnpm-lock.yaml* ./

# `--frozen-lockfile` falla si el lockfile no cuadra con package.json:
# en Docker es preferible que falle el build a desplegar versiones
# distintas de las que se developingearon.
RUN pnpm install --frozen-lockfile --prefer-offline

COPY . .

# `public/hot` podría venir en el contexto de build si alguien tuvo
# `pnpm dev` corriendo. Se borra antes de compilar.
RUN rm -f public/hot && pnpm run build


# =========================================================
# ETAPA 2: DEPENDENCIAS PHP (COMPOSER)
# =========================================================

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction \
    --no-progress

COPY . .

RUN composer dump-autoload --optimize --no-dev


# =========================================================
# ETAPA 3: RUNTIME (PHP)
# =========================================================

FROM php:8.3-fpm-alpine AS app

# `libpq` para PostgreSQL, `gd` para DomPDF (genera los PDF de
# reportes), `zip` para que Composer y Laravel puedan leer .zip,
# y las utilidades de cliente para el healthcheck.
RUN apk add --no-cache \
        libpq \
        postgresql-client \
        gd \
        zip \
        icu-dev \
        icu-libs \
        tzdata \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        opcache \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# El build de Vite se copia DESPUES de `COPY . .` a proposito: el orden
# importa. Si el host tiene un `public/build` viejo o incompleto y se
# copiara despues, pisaria el compilado de la etapa de frontend y la
# imagen serviria CSS desactualizado.
COPY --from=frontend /app/public/build ./public/build
COPY --from=vendor /app/vendor ./vendor

# `public/hot` es la causa de que las paginas salgan en HTML crudo: mientras
# exista, `@vite` genera las etiquetas apuntando al servidor HMR en vez de al
# manifest. En el contenedor no hay node, asi que ese servidor nunca va a
# levantar. Se borra y no se regenera.
#
# La comprobacion falla el build en vez de dejar pasar una imagen rota.
RUN rm -f public/hot \
    && test ! -f public/hot \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && composer dump-autoload --optimize --no-dev --no-scripts \
    && php artisan package:discover --ansi \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=http://localhost:8000 \
    COMPOSER_ALLOW_SUPERUSER=1

# Cache de bytecode y de archivos: el contenedor es de vida corta y
# cada request paga la compilacion de la vista si esto no esta.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=192'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD php -r 'exit(@fsockopen("127.0.0.1", 8000) ? 0 : 1);'

# Unico proceso del contenedor. Deliberadamente NO se ejecuta `pnpm dev`
# ni se instala node en esta etapa: los assets vienen compilados de la
# etapa de frontend y servirlos por el HMR dentro del contenedor deja las
# paginas sin estilos. El comando va en forma exec (sin `sh -c`) para que
# artisan reciba las senales de `docker stop` directamente.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]