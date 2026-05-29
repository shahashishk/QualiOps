<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScanQueue extends Model
{
    use HasFactory;

    protected $table = 'scan_queue';

    protected $fillable = [
        'project_id',
        'branch_name',
        'scan_type',
        'queue_status',
        'failure_category',
        'queued_by',
        'started_at',
        'completed_at',
        'worker_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function queuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'queued_by');
    }

    public function history(): HasOne
    {
        return $this->hasOne(ScanHistory::class, 'queue_id');
    }

    public function isStuck(?int $timeoutMinutes = null): bool
    {
        if ($this->queue_status !== 'running' || ! $this->started_at) {
            return false;
        }

        $timeoutMinutes ??= max(1, (int) ceil(config('scan.timeout_seconds', 1800) / 60));

        return $this->started_at->copy()->addMinutes($timeoutMinutes)->isPast();
    }
}
