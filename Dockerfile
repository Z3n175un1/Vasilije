# ---------- Stage 1: Build frontend ----------
FROM node:22-alpine AS node-build

WORKDIR /app

# Instala dependencias de Node
COPY package*.json ./
RUN npm ci

# Copia archivos necesarios para Vite
COPY resources ./resources
COPY public ./public
COPY vite.config.* ./
COPY jsconfig.json ./
COPY tailwind.config.* ./
COPY postcss.config.* ./

RUN npm run build


# ---------- Stage 2: Composer ----------
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

# Dependencias necesarias para ejecutar Laravel
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
COPY --from=composer-build /app ./

# Copiar assets compilados
COPY --from=node-build /app/public/build ./public/build

# Permisos
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]