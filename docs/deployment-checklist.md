# Deployment Checklist

## Before Deployment

- Confirm `.env` is present and production-safe.
- Confirm `APP_KEY` is set.
- Confirm `APP_DEBUG=false` in production.
- Run `php artisan system:preflight`.
- Run `php artisan system:health-check`.

## Application Deployment

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan queue:restart
```

## Permissions

- Ensure `storage/` is writable by the web server user.
- Ensure `bootstrap/cache/` is writable by the web server user.
- Ensure the configured scan workspace path is writable.

## Maintenance Mode

Use maintenance mode before risky releases or schema-heavy deployments:

```bash
php artisan down
php artisan up
```

Use it when:

- deploying schema changes
- rotating infrastructure credentials
- restarting queue workers during sensitive releases

## After Deployment

- Confirm the queue worker is running.
- Confirm `php artisan schedule:list` shows scheduled tasks.
- Confirm `php artisan system:health-check` reports expected readiness.
- Review `storage/logs/laravel.log` for startup failures.
