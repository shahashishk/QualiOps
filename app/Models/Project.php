<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_name',
        'description',
        'git_connection_id',
        'git_url',
        'branch_name',
        'sonar_connection_id',
        'sonar_project_key',
        'sonar_project_name',
        'scan_type',
        'schedule_enabled',
        'schedule_frequency',
        'schedule_day',
        'schedule_time',
        'active',
        'notify_pm',
        'notify_tl',
        'notify_qa',
        'notify_developer',
        'notification_custom_emails',
    ];

    protected function casts(): array
    {
        return [
            'schedule_enabled' => 'boolean',
            'active' => 'boolean',
            'notify_pm' => 'boolean',
            'notify_tl' => 'boolean',
            'notify_qa' => 'boolean',
            'notify_developer' => 'boolean',
        ];
    }

    public function gitConnection(): BelongsTo
    {
        return $this->belongsTo(GitConnection::class);
    }

    public function sonarConnection(): BelongsTo
    {
        return $this->belongsTo(SonarConnection::class);
    }

    public function scanQueue(): HasMany
    {
        return $this->hasMany(ScanQueue::class);
    }

    public function scanHistories(): HasMany
    {
        return $this->hasMany(ScanHistory::class);
    }

    public function sonarMetricSnapshots(): HasMany
    {
        return $this->hasMany(SonarMetricSnapshot::class);
    }

    public function latestSonarMetricSnapshot(): HasOne
    {
        return $this->hasOne(SonarMetricSnapshot::class)->latestOfMany('synced_at');
    }

    public function notificationHistories(): HasMany
    {
        return $this->hasMany(NotificationHistory::class);
    }
}
