FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libssl-dev \
    libzip-dev \
    pkg-config \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install mongodb && docker-php-ext-enable mongodb

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock* ./

RUN composer update --no-dev --optimize-autoloader --no-interaction --no-audit --no-scripts

COPY . .

RUN composer dump-autoload --optimize --no-interaction
RUN composer run-script post-install-cmd --no-interaction || true

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]