# Run on http://benefitowo.webdev:8080/

1) Map the hostname on your machine:

**macOS/Linux** add to `/etc/hosts`:
```
127.0.0.1   benefitowo.webdev
```
(If Docker runs on another host/IP, put that IP instead.)

**Windows** add to `C:\Windows\System32\drivers\etc\hosts`:
```
127.0.0.1   benefitowo.webdev
```

2) Start the stack:
```bash
docker compose up --build
```

3) Open:
```
http://benefitowo.webdev:8080/
```

Notes:
- Nginx is configured with `server_name benefitowo.webdev` and will drop requests for other hosts.
- Postgres on host: `localhost:5432` (user: app, pass: app, db: app).
- Symfony `DATABASE_URL` is already set in `docker-compose.yml`.
