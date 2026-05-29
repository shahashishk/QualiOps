<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanScheduleLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheduled_at',
        'eligible_projects_count',
        'queued_projects_count',
        'skipped_projects_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }
}
