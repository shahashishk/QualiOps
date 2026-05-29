# Supervisor Setup

Use Supervisor to keep Laravel queue workers running continuously in production.

## Queue Worker Program

Example `supervisor` program entry:

```ini
[program:qualiops-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/qualiops/artisan queue:work --sleep=3 --tries=1 --timeout=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/qualiops/storage/logs/queue-worker.log
stopwaitsecs=3600
```

## Scheduler

Supervisor is usually not required for the scheduler itself. Prefer one server cron entry:

```bash
* * * * * cd /var/www/qualiops && php artisan schedule:run >> /dev/null 2>&1
```

If your platform requires a long-running scheduler helper, keep it separate from queue workers and log it independently.

## Log Files

- Application logs: `storage/logs/laravel.log`
- Queue worker logs: `storage/logs/queue-worker.log`
- Supervisor main logs: platform dependent, commonly `/var/log/supervisor/`

## Restart Commands

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart qualiops-queue:*
php artisan queue:restart
```

## Troubleshooting

- If code changes are not picked up, run `php artisan queue:restart`.
- If jobs stall, confirm `queue.default`, worker timeout, and database or Redis connectivity.
- If scan jobs fail immediately, re-run `php artisan system:health-check`.
- If Supervisor shows `FATAL`, confirm the PHP path, working directory, and file permissions.
