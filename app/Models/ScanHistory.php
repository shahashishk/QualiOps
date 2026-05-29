<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'branch_name',
        'scan_type',
        'triggered_by',
        'queue_id',
        'sonar_project_key',
        'sonar_dashboard_url',
        'status',
        'failure_category',
        'started_at',
        'completed_at',
        'duration_seconds',
        'total_bugs',
        'total_vulnerabilities',
        'total_code_smells',
        'coverage',
        'duplicated_lines',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'coverage' => 'decimal:2',
            'duplicated_lines' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(ScanQueue::class, 'queue_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ScanLog::class);
    }

    public function notificationHistories(): HasMany
    {
        return $this->hasMany(NotificationHistory::class);
    }
}
