FROM php:8.2-fpm

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo_mysql

# Copiar composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Primero copia TODO el proyecto
COPY . .

# Recién aquí ejecuta composer
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Exponer puerto
EXPOSE 8000

# Comando final
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

