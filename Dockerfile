# ---------- Etapa 1: imagen base con extensiones necesarias ----------
FROM php:8.2-fpm AS base

# Evita preguntas interactivas
ARG DEBIAN_FRONTEND=noninteractive

# Instalar utilidades y dependencias necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    zip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo_mysql mbstring bcmath

# Copiar composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiar solo archivos de composer para aprovechar cache de Docker
COPY composer.json composer.lock ./

# Instalar dependencias de PHP sin dev (cacheable)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copiar el resto de la app
COPY . .

# Permisos para storage y cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache || true \
    && chmod -R 775 /app/storage /app/bootstrap/cache || true

# Exponer puerto (Railway mapeará internamente)
EXPOSE 8000

# Comando por defecto (en producción ideal es usar nginx + php-fpm, pero para Railway esto funciona)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
