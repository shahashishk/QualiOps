<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SonarMetricSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'sonar_project_key',
        'bugs',
        'vulnerabilities',
        'code_smells',
        'security_hotspots',
        'coverage',
        'duplicated_lines_density',
        'reliability_rating',
        'security_rating',
        'maintainability_rating',
        'technical_debt',
        'quality_gate_status',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'coverage' => 'decimal:2',
            'duplicated_lines_density' => 'decimal:2',
            'synced_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
