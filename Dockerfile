
# Stage 1: Build Laravel Vite and Tailwind assets
FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build


# Stage 2: PHP 8.3 and Apache
FROM php:8.3-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip ca-certificates \
    libonig-dev libzip-dev libicu-dev \
    libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
    pdo_mysql mbstring bcmath intl zip gd \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Serve Laravel's public directory on Render port 10000
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/*:80/*:10000/' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

ENV PORT=10000

WORKDIR /var/www/html

# Install production PHP dependencies
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

# Copy application source
COPY . .

# Copy compiled frontend assets
COPY --from=frontend /app/public/build ./public/build

# Prepare Laravel directories
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && composer dump-autoload --no-dev --optimize \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 10000

CMD ["apache2-foreground"]
