<?php

namespace App\Events;

use App\Models\Project;
use App\Models\SonarMetricSnapshot;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QualityGateFailedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Project $project,
        public readonly SonarMetricSnapshot $snapshot,
    ) {
    }
}
