# Scheduler Setup

## Purpose

QualiOps uses the Laravel Scheduler to evaluate project schedules and create `scan_queue` records from application code. Background scan execution remains the responsibility of the Laravel queue worker.

## Command

Run the scheduler dispatch command manually:

```bash
php artisan scans:dispatch-scheduled
```

Preview only, without creating queue records or dispatching jobs:

```bash
php artisan scans:dispatch-scheduled --dry-run
```

Dry-run mode:

- shows which projects are eligible right now
- does not create `scan_queue` records
- does not dispatch `RunProjectScanJob`
- does not clone repositories
- does not run scanners

## Laravel Scheduler

QualiOps registers the command in application code through Laravel Scheduler:

```php
Schedule::command('scans:dispatch-scheduled')->everyMinute();
```

This keeps schedule definitions version-controlled in the application and requires only one server cron entry.

## Cron Setup

Add one cron entry on Linux:

```bash
* * * * * cd /var/www/qualiops && php artisan schedule:run >> /dev/null 2>&1
```

If you are using Windows Task Scheduler, run this every minute:

```powershell
php artisan schedule:run
```

## Queue Worker

The scheduler only prepares work. The queue worker performs background execution when runtime checks pass.

Start a worker manually:

```bash
php artisan queue:work --queue=scans --tries=1 --timeout=1800
```

## Duplicate Prevention

The scheduler will not queue the same project and branch when an existing `pending` or `running` queue record already exists for that combination.

## Weekly Scheduling

Weekly schedules require:

- `schedule_enabled = true`
- `schedule_frequency = weekly`
- `schedule_day` set to `monday` through `sunday`
- `schedule_time` set in `HH:MM`

## Production Notes

- Ensure `schedule:run` executes every minute.
- Keep the queue worker running continuously.
- Review `/system-health` before enabling live runtime dispatch.
- If runtime prerequisites are missing, scheduled scans can still create queue records, but jobs are not dispatched automatically.
- Use `php artisan scans:dispatch-scheduled --dry-run` before first production rollout to confirm eligibility behavior.
