# Production Deployment Guide

## ✅ Environment Successfully Switched to Production

Your Symfony backend is now configured for production mode.

## Current Configuration

```bash
APP_ENV=prod
APP_DEBUG=0
```

- **Environment**: `prod`
- **Debug Mode**: `false` (disabled)
- **Cache Directory**: `var/cache/prod`
- **Symfony Version**: 7.2.9

## What Was Fixed

1. ✅ Changed `APP_DEBUG` from `1` to `0` in `.env`
2. ✅ Cleared all cache directories
3. ✅ Rebuilt production cache with debug disabled
4. ✅ Warmed up production cache
5. ✅ Fixed Monolog configuration for production logs

## Verification

Check that production environment is active:

```bash
php bin/console about --env=prod
```

Should show:
```
Environment: prod
Debug: false
```

## Cache Management

### Clear Production Cache
```bash
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear --env=prod --no-debug
```

### Warmup Production Cache
```bash
APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup --env=prod --no-debug
```

### Remove All Caches (Nuclear Option)
```bash
rm -rf var/cache/*
APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup --env=prod --no-debug
```

## Web Server Configuration

### For Apache/Nginx

Make sure your web server passes the correct environment variables. The `public/index.php` reads from `$context['APP_ENV']` and `$context['APP_DEBUG']`.

Your `.env` file is already configured correctly:
```env
APP_ENV=prod
APP_DEBUG=0
```

### For FrankenPHP

If using FrankenPHP (Docker), make sure your Docker environment variables are set:

```yaml
# docker-compose.yml
environment:
  APP_ENV: prod
  APP_DEBUG: 0
```

### For Built-in PHP Server (Development Only)

```bash
APP_ENV=prod APP_DEBUG=0 php -S localhost:8000 -t public
```

## Production Checklist

- [x] `APP_ENV=prod` in `.env`
- [x] `APP_DEBUG=0` in `.env`
- [x] Production cache cleared and warmed up
- [x] Monolog configured for production logs
- [ ] Web server environment variables configured (if applicable)
- [ ] SSL/HTTPS enabled
- [ ] Database credentials secured
- [ ] JWT keys secured
- [ ] CORS configured for production domains

## Log Files in Production

### Location
- Main logs: `/var/logs/prod.log` (JSON format)
- Subdomain logs: `/var/logs/subdomain.log` (JSON format)
- Deprecation logs: `php://stderr` (JSON format)

### View Logs
```bash
# Real-time production logs
tail -f /var/logs/prod.log

# Real-time subdomain logs
tail -f /var/logs/subdomain.log

# Search for errors
grep "ERROR" /var/logs/prod.log
```

## Performance Optimizations

### OPcache (Already Enabled)
Your PHP has OPcache enabled, which significantly improves performance.

### Composer Autoloader Optimization
```bash
composer dump-autoload --optimize --no-dev --classmap-authoritative
```

### Doctrine Metadata Cache
Doctrine metadata is cached in production mode by default.

## Common Issues

### Issue: Web requests still use dev environment

**Solution**: Check your web server configuration and restart it:
```bash
# For Apache
sudo systemctl restart apache2

# For Nginx + PHP-FPM
sudo systemctl restart nginx
sudo systemctl restart php-fpm

# For Docker
docker-compose restart
```

### Issue: Changes not reflected

**Solution**: Clear cache and restart web server:
```bash
rm -rf var/cache/*
APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup --env=prod --no-debug
# Then restart your web server
```

### Issue: Permission errors

**Solution**: Set correct permissions:
```bash
chmod -R 777 var/cache/
chmod -R 777 var/log/
```

## Security Notes

### Production Security

1. **Never enable debug mode in production** (`APP_DEBUG=0`)
2. **Keep `.env` file secure** (not in git, proper permissions)
3. **Use environment variables** for sensitive data
4. **Enable HTTPS/SSL** for all production traffic
5. **Regular security updates** for Symfony and dependencies

### Environment Variables Priority

Symfony loads environment variables in this order (last wins):
1. `.env` - Committed defaults
2. `.env.local` - Local overrides (not committed)
3. `.env.prod` - Production-specific (optional)
4. `.env.prod.local` - Local production overrides (not committed)
5. Real environment variables - From web server/shell

## Deployment Workflow

### Recommended Deployment Steps

1. **Pull latest code**
   ```bash
   git pull origin main
   ```

2. **Install dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Run migrations**
   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction --env=prod
   ```

4. **Clear and warmup cache**
   ```bash
   APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear --env=prod --no-debug
   APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup --env=prod --no-debug
   ```

5. **Restart web server**
   ```bash
   sudo systemctl restart nginx  # or your web server
   ```

## Monitoring

### Check Application Status
```bash
php bin/console about --env=prod
```

### Monitor Logs
```bash
# Watch for errors
tail -f /var/logs/prod.log | grep ERROR

# Watch subdomain creation
tail -f /var/logs/subdomain.log
```

### Check for Errors
```bash
php bin/console debug:container --env=prod
php bin/console debug:router --env=prod
```

## Rollback Procedure

If something goes wrong:

1. **Revert code**
   ```bash
   git reset --hard HEAD~1
   ```

2. **Clear cache**
   ```bash
   rm -rf var/cache/*
   php bin/console cache:warmup --env=prod
   ```

3. **Restart web server**

## Next Steps

1. ✅ Verify your API endpoints are working correctly
2. ✅ Test subdomain creation with production settings
3. ✅ Monitor logs for any errors
4. ✅ Set up automated deployments (optional)
5. ✅ Configure production monitoring tools (optional)

## Testing Production Mode Locally

You can test production mode locally:

```bash
# Clear all caches
rm -rf var/cache/*

# Start with production environment
APP_ENV=prod APP_DEBUG=0 php -S localhost:8000 -t public

# Test your API
curl http://localhost:8000/api/health
```

## Important Notes

- Production mode disables the Symfony profiler and debug toolbar
- Error messages are less detailed (for security)
- Twig templates are cached aggressively
- Routing is cached
- Performance is significantly better than dev mode

Your production environment is now properly configured! 🚀


