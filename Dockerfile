# ---------- Stage 1: Build frontend ----------
FROM node:22-alpine AS node-build

WORKDIR /app

# Copiar dependencias de Node
COPY package.json ./
RUN corepack enable && corepack prepare pnpm@latest --activate && pnpm install

# Copiar el resto del proyecto para que Vite tenga acceso a todos los archivos
COPY . .

# Compilar assets
RUN pnpm run build


# ---------- Stage 2: Instalar dependencias PHP ----------
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


# ---------- Stage 3: Runtime ----------
FROM php:8.3-fpm-alpine

# Instalar dependencias y extensiones PHP
RUN apk add --no-cache \
    postgresql-libs \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma \
    libxml2 \
    zip \
    unzip \
    icu-libs \
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

# Copiar aplicación
COPY --from=composer-build /app .

# Copiar assets compilados
COPY --from=node-build /app/public/build ./public/build

# Crear directorios necesarios y permisos
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]