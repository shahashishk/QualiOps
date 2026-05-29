<?php

namespace App\Events;

use App\Models\Project;
use App\Models\ScanHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScanLifecycleEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $eventType,
        public readonly Project $project,
        public readonly ScanHistory $scanHistory,
        public readonly bool $scheduled = false,
    ) {
    }
}
