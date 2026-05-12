FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libssl-dev \
    libzip-dev \
    pkg-config \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install MongoDB PHP extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Allow Composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy composer files first for better Docker caching
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy the rest of the application
COPY . .

# Run post-install scripts
RUN composer run-script post-install-cmd --no-interaction || true

# Generate JWT keys if they don't exist
RUN mkdir -p config/jwt && \
    if [ ! -f config/jwt/private.pem ]; then \
        openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:${JWT_PASSPHRASE:-aurora_jwt_passphrase_change_me} 2>/dev/null || true; \
        openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:${JWT_PASSPHRASE:-aurora_jwt_passphrase_change_me} 2>/dev/null || true; \
    fi

# Clear and warm up cache
RUN php bin/console cache:clear --env=prod --no-interaction 2>/dev/null || true

EXPOSE 8000

# Use Symfony's built-in server for simplicity
# In production, you'd use nginx + php-fpm
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
