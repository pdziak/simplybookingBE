#!/bin/bash

# Setup script for authentication system

echo "Setting up authentication system..."

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "Error: Please run this script from the api directory"
    exit 1
fi

# Install dependencies
echo "Installing dependencies..."
composer install

# Generate JWT keys if they don't exist
if [ ! -f "config/jwt/private.pem" ]; then
    echo "Generating JWT keys..."
    mkdir -p config/jwt
    openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:your-jwt-passphrase-here
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem -passin pass:your-jwt-passphrase-here
    echo "JWT keys generated successfully"
fi

# Run database migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

# Clear cache
echo "Clearing cache..."
php bin/console cache:clear

echo "Authentication system setup complete!"
echo ""
echo "You can now:"
echo "1. Start the server: php -S localhost:8000 -t public"
echo "2. Test the API endpoints using the documentation in AUTHENTICATION.md"
echo "3. Run tests: php bin/phpunit tests/Api/AuthTest.php"
echo ""
echo "API Documentation available at: http://localhost:8000/docs"
