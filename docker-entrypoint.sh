#!/bin/bash
set -e

echo "=== Aurora Backend Starting ==="

# Generate JWT keys if they don't exist
mkdir -p config/jwt
if [ ! -f config/jwt/private.pem ]; then
    echo "Generating JWT keypair..."
    if [ -z "$JWT_PASSPHRASE" ]; then
        echo "WARNING: JWT_PASSPHRASE not set, using default"
        export JWT_PASSPHRASE="aurora_jwt_passphrase_change_me"
    fi
    openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa \
        -pkeyopt rsa_keygen_bits:4096 \
        -pass pass:${JWT_PASSPHRASE} 2>&1 || echo "WARNING: Failed to generate private key"
    openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem \
        -pubout -passin pass:${JWT_PASSPHRASE} 2>&1 || echo "WARNING: Failed to generate public key"
    echo "JWT keys generated."
else
    echo "JWT keys already exist, skipping generation."
fi

# Clear cache (non-fatal)
echo "Clearing cache..."
php bin/console cache:clear --env=prod --no-interaction 2>&1 || echo "WARNING: cache:clear failed, continuing anyway"

# Start the server
echo "Starting PHP server on 0.0.0.0:8000..."
exec php -S 0.0.0.0:8000 -t public
