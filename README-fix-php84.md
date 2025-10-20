# PHP 8.4 fix for Composer platform check

Your Composer dependencies require PHP >= 8.4. This package bumps the image to **php:8.4-fpm-alpine**.

## Steps
```bash
# make sure hosts entry exists:
# 127.0.0.1 simplybooking.webdev

docker compose build --no-cache php
docker compose up -d

# verify PHP version
docker compose exec php php -v
```

If you still see the platform error, remove vendor and reinstall inside the container:
```bash
rm -rf vendor
docker compose exec php composer install
```

### Temporary dev workaround (not recommended)
If upgrading PHP isn't possible, you could bypass platform checks:
```bash
composer install --ignore-platform-reqs
```
But prefer running PHP 8.4 to match your dependencies.
