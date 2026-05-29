<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_history_id',
        'log_level',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function history(): BelongsTo
    {
        return $this->belongsTo(ScanHistory::class, 'scan_history_id');
    }
}
