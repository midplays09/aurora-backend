FROM php:8.3-cli

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

RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

RUN composer run-script post-install-cmd --no-interaction || true


EXPOSE 8000

CMD mkdir -p config/jwt && \
    if [ ! -f config/jwt/private.pem ]; then \
    openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa \
    -pkeyopt rsa_keygen_bits:4096 \
    -pass pass:${JWT_PASSPHRASE} && \
    openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem \
    -pubout -passin pass:${JWT_PASSPHRASE}; \
    fi && \
    php bin/console cache:clear --env=prod --no-interaction && \
    php -S 0.0.0.0:8000 -t public