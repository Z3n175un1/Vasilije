# =========================================================
# ETAPA 1: FRONTEND
# PNPM + VITE
# =========================================================

FROM node:22-alpine AS frontend-builder

WORKDIR /app

# Activar pnpm
RUN corepack enable

# Copiar package.json
COPY package.json ./

# Instalar dependencias
RUN pnpm install

# Copiar el proyecto
COPY . .

# Compilar Vite para producción
RUN pnpm run build


# =========================================================
# ETAPA 2: COMPOSER
# =========================================================

FROM composer:2 AS vendor

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

RUN composer dump-autoload --optimize


# =========================================================
# ETAPA 3: PHP / LARAVEL
# =========================================================

FROM php:8.3-fpm

# Dependencias del sistema
RUN apt-get update \
    && apt-get install -y \
        libpq-dev \
        unzip \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PostgreSQL
RUN docker-php-ext-install -j$(nproc) \
    pgsql \
    pdo_pgsql

WORKDIR /app

# Copiar aplicación
COPY . .

# Copiar vendor
COPY --from=vendor /app/vendor ./vendor

# Copiar build de Vite
COPY --from=frontend-builder /app/public/build ./public/build

# Crear directorios Laravel
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache

# Permisos
RUN chmod -R 775 storage bootstrap/cache

# Laravel package discovery
RUN php artisan package:discover --ansi

# Producción
ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 10000

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
