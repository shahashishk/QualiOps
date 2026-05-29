# Queue Recovery

## Purpose

QualiOps queue recovery controls help operators stabilize scan execution without running scanners directly. These controls are focused on queue actions, stuck-job recovery, and safe requeue behavior.

## Available Queue Actions

- Retry failed scan
- Cancel pending scan
- Requeue completed or cancelled scan
- Mark stuck running scan as failed

## Safety Rules

- Completed scans cannot be cancelled.
- Running scans cannot be retried.
- Running scans are not deletable through queue policy checks.
- Duplicate `pending` or `running` scans for the same project and branch are blocked.

## Failure Categories

Queue and history records can store one of these failure categories:

- `git_clone_failed`
- `stack_detection_failed`
- `scanner_failed`
- `sonar_upload_failed`
- `timeout`
- `cleanup_failed`
- `unknown`

## Recover Stuck Running Scans

Preview stuck scans without changing records:

```bash
php artisan scans:recover-stuck --dry-run
```

Override the timeout window in minutes:

```bash
php artisan scans:recover-stuck --timeout-minutes=45
```

Apply recovery and mark stuck scans as failed:

```bash
php artisan scans:recover-stuck
```

## Timeout Behavior

- Running scans older than the configured timeout are considered stuck.
- Recovery marks the queue entry as `failed`.
- The failure category becomes `timeout`.
- A timeout log entry is written to linked scan history when available.
- An audit log entry is stored for traceability.

## Audit Events

These queue recovery actions write audit entries:

- `scan.retried`
- `scan.cancelled`
- `scan.requeued`
- `scan.timeout_recovered`

## Production Notes

- Schedule `php artisan scans:recover-stuck` from Laravel Scheduler or run it operationally as needed.
- Review queue detail pages for `Stuck Running` warnings.
- Use queue filters for status and failure reason during incident triage.
- Keep queue workers running separately; recovery does not replace worker supervision.
