# ---------- ETAPA 1: Frontend ----------
FROM node:22-slim AS frontend-builder

WORKDIR /app

# Habilitar pnpm v9 (compatible con pnpm-lock.yaml v9)
RUN corepack enable && corepack prepare pnpm@9 --activate

# Copiar solo package.json (el lock se regenera por plataforma)
COPY package.json ./

# Instalar dependencias
RUN pnpm install --no-frozen-lockfile

# Copiar resto del proyecto
COPY . .

# Build de Vite
RUN pnpm run build


# ---------- ETAPA 2: Composer ----------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

COPY . .

# Se regenera autoload con el contexto completo
RUN composer dump-autoload --optimize


# ---------- ETAPA 3: PHP ----------
FROM php:8.3-fpm-alpine

WORKDIR /app

# Dependencias del sistema
RUN apk add --no-cache \
    postgresql-libs \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma \
    icu-libs \
    libzip \
    unzip \
    git \
    bash

# Build deps + extensiones PHP
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    libzip-dev \
 && docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
 && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    bcmath \
    exif \
    pcntl \
    gd \
    intl \
    zip \
 && apk del .build-deps

# OPCache (mejora rendimiento)
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Copiar app
COPY . .

# Vendor
COPY --from=vendor /app/vendor ./vendor

# Build frontend
COPY --from=frontend-builder /app/public/build ./public/build

# Permisos Laravel
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

RUN php artisan package:discover --ansi

ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 10000

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
