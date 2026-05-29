<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AuditLogService
{
    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     */
    public function record(
        string $action,
        string|Model|null $entity = null,
        ?User $user = null,
        array $oldValues = [],
        array $newValues = [],
        ?Request $request = null
    ): AuditLog {
        [$entityType, $entityId] = $this->entityParts($entity);
        $request ??= request();

        return AuditLog::query()->create([
            'user_id' => $user?->id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => $this->sanitizeValues($oldValues) ?: null,
            'new_values' => $this->sanitizeValues($newValues) ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public function sanitizeValues(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if ($this->isSensitiveKey($normalizedKey)) {
                $sanitized[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeValues($value);
                continue;
            }

            if (is_string($value) && $this->containsSecretLikeValue($normalizedKey, $value)) {
                $sanitized[$key] = '***';
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    public function summarize(AuditLog $auditLog): string
    {
        $old = collect($auditLog->old_values ?? []);
        $new = collect($auditLog->new_values ?? []);

        if ($new->isNotEmpty() || $old->isNotEmpty()) {
            $keys = $new->keys()->merge($old->keys())->unique()->values();

            return $keys->take(4)->map(function (string $key) use ($old, $new) {
                $before = $this->valueLabel($old->get($key));
                $after = $this->valueLabel($new->get($key));

                if ($before === null && $after !== null) {
                    return str($key)->replace('_', ' ')->title().': '.$after;
                }

                if ($before !== null && $after === null) {
                    return str($key)->replace('_', ' ')->title().': removed';
                }

                if ($before !== $after) {
                    return str($key)->replace('_', ' ')->title().': '.$before.' -> '.$after;
                }

                return str($key)->replace('_', ' ')->title().': '.$after;
            })->join(' | ');
        }

        return str($auditLog->action)->replace(['.', '_'], ' ')->title()->toString();
    }

    public function statusBadge(AuditLog $auditLog): string
    {
        $action = strtolower($auditLog->action);

        return match (true) {
            str_contains($action, 'failed'), str_contains($action, 'failure'), str_contains($action, 'cleanup_failure') => 'danger',
            str_contains($action, 'deleted'), str_contains($action, 'cancelled'), str_contains($action, 'deactivated') => 'warning',
            str_contains($action, 'logged_in'), str_contains($action, 'created'), str_contains($action, 'queued'), str_contains($action, 'synced'), str_contains($action, 'retried') => 'success',
            default => 'secondary',
        };
    }

    /**
     * @return array{0: ?string, 1: ?int}
     */
    private function entityParts(string|Model|null $entity): array
    {
        if ($entity instanceof Model) {
            return [$entity::class, (int) $entity->getKey()];
        }

        if (is_string($entity)) {
            return [$entity, null];
        }

        return [null, null];
    }

    private function isSensitiveKey(string $key): bool
    {
        return str_contains($key, 'token')
            || str_contains($key, 'password')
            || str_contains($key, 'secret')
            || str_contains($key, 'ssh_private_key')
            || str_contains($key, 'remember_token');
    }

    private function containsSecretLikeValue(string $key, string $value): bool
    {
        return $this->isSensitiveKey($key)
            || str_contains(strtolower($value), '-----begin')
            || str_contains(strtolower($value), 'bearer ');
    }

    private function valueLabel(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_array($value)) {
            return collect(Arr::flatten($value))->map(fn ($item) => (string) $item)->join(', ');
        }

        return (string) $value;
    }
}
