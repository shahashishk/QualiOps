<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'auditable_type',
        'auditable_id',
        'description',
        'metadata',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AuditLog $auditLog): void {
            if ($auditLog->entity_type === null && $auditLog->auditable_type === null) {
                $auditLog->entity_type = 'system';
                $auditLog->auditable_type = 'system';
            }

            if ($auditLog->entity_id === null && $auditLog->auditable_id === null) {
                $auditLog->entity_id = 0;
                $auditLog->auditable_id = 0;
            }

            if ($auditLog->entity_type !== null && $auditLog->auditable_type === null) {
                $auditLog->auditable_type = $auditLog->entity_type;
            }

            if ($auditLog->entity_id !== null && $auditLog->auditable_id === null) {
                $auditLog->auditable_id = $auditLog->entity_id;
            }

            if ($auditLog->auditable_type !== null && $auditLog->entity_type === null) {
                $auditLog->entity_type = $auditLog->auditable_type;
            }

            if ($auditLog->auditable_id !== null && $auditLog->entity_id === null) {
                $auditLog->entity_id = (int) $auditLog->auditable_id;
            }

            if ($auditLog->new_values !== null && $auditLog->metadata === null) {
                $auditLog->metadata = $auditLog->new_values;
            }

            if ($auditLog->metadata !== null && $auditLog->new_values === null) {
                $auditLog->new_values = $auditLog->metadata;
            }

            if (blank($auditLog->description) && filled($auditLog->action)) {
                $auditLog->description = Str::of($auditLog->action)
                    ->replace(['.', '_'], ' ')
                    ->title()
                    ->toString();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
