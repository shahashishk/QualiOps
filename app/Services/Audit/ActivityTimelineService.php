<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityTimelineService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function forEntity(Model|string $entity, ?int $entityId = null, int $limit = 12)
    {
        [$entityType, $resolvedId] = $entity instanceof Model
            ? [$entity::class, (int) $entity->getKey()]
            : [$entity, $entityId];

        return AuditLog::query()
            ->with('user')
            ->where('entity_type', $entityType)
            ->when($resolvedId !== null, fn (Builder $query) => $query->where('entity_id', $resolvedId))
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (AuditLog $log) => $this->present($log));
    }

    public function paginated(array $filters): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('user')
            ->when(filled($filters['user_id'] ?? null), fn (Builder $query) => $query->where('user_id', $filters['user_id']))
            ->when(filled($filters['entity_type'] ?? null), fn (Builder $query) => $query->where('entity_type', $filters['entity_type']))
            ->when(filled($filters['action'] ?? null), fn (Builder $query) => $query->where('action', $filters['action']))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('created_at')
            ->paginate(15)
            ->through(fn (AuditLog $log) => $this->present($log));
    }

    public function present(AuditLog $auditLog): array
    {
        return [
            'model' => $auditLog,
            'action' => $auditLog->action,
            'action_label' => str($auditLog->action)->replace(['.', '_'], ' ')->title()->toString(),
            'user_name' => $auditLog->user?->name ?? 'System',
            'timestamp' => $auditLog->created_at,
            'badge' => $this->auditLogService->statusBadge($auditLog),
            'summary' => $this->auditLogService->summarize($auditLog),
            'entity_label' => class_basename((string) $auditLog->entity_type),
        ];
    }

    public function recent(int $limit = 6)
    {
        return AuditLog::query()
            ->with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (AuditLog $log) => $this->present($log));
    }
}
