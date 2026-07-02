# ==========================
# Stage 1 - Frontend
# ==========================
FROM node:22-alpine AS node-build

WORKDIR /app

# Habilitar pnpm
RUN corepack enable

# Copiar únicamente los archivos necesarios para instalar dependencias
COPY package.json pnpm-lock.yaml ./

# Instalar dependencias usando el lockfile
RUN pnpm install --frozen-lockfile

# Copiar el resto del proyecto
COPY . .

# Compilar Vite
RUN pnpm run build


# ==========================
# Stage 2 - Composer
# ==========================
FROM composer:2 AS composer-build

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

COPY . .

RUN composer dump-autoload --optimize


# ==========================
# Stage 3 - Runtime
# ==========================
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-libs \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma \
    libxml2 \
    icu-libs \
    zip \
    unzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        icu-dev \
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
    && apk del .build-deps

WORKDIR /var/www

# Copiar aplicación PHP
COPY --from=composer-build /app .

# Copiar assets compilados
COPY --from=node-build /app/public/build ./public/build

# Directorios necesarios para Laravel
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]