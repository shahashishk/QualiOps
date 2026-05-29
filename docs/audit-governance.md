# Audit Governance

## Purpose

QualiOps audit governance provides traceability for administrative, project, connection, queue, scheduler, and metrics activity. Audit records are stored locally and are designed for review, incident reconstruction, and compliance evidence.

## Captured Events

Examples of tracked events include:

- login and logout
- user create, update, activate, deactivate, delete
- role create, update, delete
- project create, update, activate, deactivate
- Git connection create, update, activate, deactivate, test
- SonarQube connection create, update, activate, deactivate, test
- scan queued, cancelled, retried, requeued, failed
- scheduler execution
- SonarQube metrics sync
- workspace created, deleted, cleanup failure

## Audit Record Shape

Audit records store:

- acting user when available
- entity type and entity id
- action name
- old values
- new values
- request IP address
- request user agent
- created timestamp

Sensitive fields such as passwords, tokens, secrets, and SSH private keys are masked before storage.

## Activity Timelines

Activity timelines are available on:

- Project Detail
- User Detail
- Scan History Detail

The dashboard also shows a Recent Activities widget for quick governance visibility.

## Audit Retention

Retention is controlled by the `audit_log_retention_days` system setting.

Default seed value:

- `90`

## Cleanup Command

Preview cleanup:

```bash
php artisan audit:cleanup --dry-run
```

Delete expired audit logs:

```bash
php artisan audit:cleanup
```

The command removes logs older than the configured retention window.

## Operational Notes

- Audit logging focuses on governance and traceability, not notification delivery.
- Secret values are masked before persistence.
- Recent activity views should be reviewed after administrative or queue-recovery actions.
- Audit cleanup should be run periodically according to your retention policy.
