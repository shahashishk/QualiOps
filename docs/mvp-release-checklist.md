# MVP Release Checklist

## Deployment Steps

1. Put the application into maintenance mode if the release includes migrations or worker restarts.
2. Pull the target release or deploy the application package.
3. Run:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
php artisan queue:restart
```

4. Run:

```bash
php artisan system:preflight
php artisan system:health-check
```

5. Bring the application back online.

## Worker Setup

- Run at least one Laravel queue worker for background scans, notifications, and exports.
- Use Supervisor or an equivalent process manager in production.
- After each deployment, run `php artisan queue:restart`.
- Validate worker readiness from the System Health page.

## Scheduler Setup

- Configure one server cron entry for Laravel scheduler execution:

```bash
* * * * * cd /var/www/qualiops && php artisan schedule:run >> /dev/null 2>&1
```

- Confirm scheduled tasks with `php artisan schedule:list`.
- Use `php artisan scans:dispatch-scheduled --dry-run` before first production enablement.

## Rollback Plan

1. Put the portal into maintenance mode.
2. Stop or restart queue workers to prevent new jobs from starting during rollback.
3. Re-deploy the previous stable release.
4. If a migration must be reversed, perform it only after confirming the data impact.
5. Run `php artisan optimize:clear`, then `php artisan optimize`.
6. Restart workers and verify System Health again.

## Backup Guidance

- Back up the MySQL database before each production release.
- Back up `storage/app`, especially generated exports and any operational artifacts you need to retain.
- Preserve `.env` and deployment-specific secrets outside the repo.
- Retain recent application logs and queue failure history before cleanup jobs run.

## Production Checklist

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` configured
- database reachable
- queue backend reachable
- queue worker running
- scheduler cron active
- workspace path writable
- Git runtime available
- SonarQube runtime dependencies installed if real scans will run
- active Git and SonarQube connections tested
- notifications mail transport configured if email alerts are enabled
- export/download storage verified

## Maintenance Mode Guidance

Use:

```bash
php artisan down
php artisan up
```

Enable maintenance mode during:

- schema deployments
- credential rotation
- worker restarts during sensitive releases
- rollback operations
