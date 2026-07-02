# ---------- ETAPA 1: Frontend ----------
FROM node:20-alpine AS frontend-builder

WORKDIR /app

RUN corepack enable && corepack prepare pnpm@9.12.0 --activate

COPY . .

RUN pnpm install --no-frozen-lockfile
RUN pnpm run build


# ---------- ETAPA 2: Composer ----------
FROM composer:2 AS vendor

WORKDIR /app

COPY . .

# Evitar scripts Laravel durante install
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-reqs


# ---------- ETAPA 3: PHP ----------
FROM php:8.2-cli-alpine

WORKDIR /app

# Dependencias del sistema
RUN apk add --no-cache \
    postgresql-client \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    freetype-dev \
    jpeg-dev \
    oniguruma-dev \
    icu-dev \
    unzip \
    git

# Extensiones PHP
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg && \
    docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    zip \
    bcmath \
    intl \
    gd \
    opcache

# OPcache
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Copiar aplicación
COPY . .

# Copiar vendor
COPY --from=vendor /app/vendor ./vendor

# Copiar frontend build
COPY --from=frontend-builder /app/public/build ./public/build

# Laravel folders
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 10000

CMD ["sh", "-c", "chmod -R 775 storage bootstrap/cache && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]