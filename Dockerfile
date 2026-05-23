FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev libicu-dev zip unzip nodejs npm \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip opcache intl \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
    storage/framework/testing storage/logs storage/app/public bootstrap/cache

RUN npm run build
RUN composer dump-autoload --optimize --no-scripts
RUN chmod -R 775 storage bootstrap/cache

# Health endpoint for Railway
RUN echo '<?php http_response_code(200); header("Content-Type: text/plain"); echo "ok"; ?>' > public/health.php

ENV PORT=8080
EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t public router.php"]
