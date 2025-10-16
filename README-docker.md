# API Platform 7.2 + Nginx + Postgres (Docker)

## Quick start
```bash
docker compose up --build
# App: http://localhost:8080
# Postgres: localhost:5432 (user: app, pass: app, db: app)
```

Symfony will read `DATABASE_URL` from the `php` service environment:
```
postgresql://app:app@db:5432/app?serverVersion=16&charset=utf8
```

## Common commands
```bash
# Composer in container
docker compose exec php composer install

# Symfony console
docker compose exec php php bin/console about

# Doctrine migration (example)
docker compose exec php php bin/console doctrine:database:create --if-not-exists
docker compose exec php php bin/console doctrine:migrations:migrate -n

# psql from host (requires ports: 5432:5432)
psql -h localhost -U app -d app
```

## Notes
- The `db` container stores data in a named volume `pgdata`.
- If you already have a local Postgres on port 5432, change the host port mapping in `docker-compose.yml`.
- For production, consider enabling Opcache JIT and setting proper caching headers in `nginx.conf`.
