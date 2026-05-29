<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemWarningEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param array<int, string> $warnings
     */
    public function __construct(
        public readonly string $eventType,
        public readonly array $warnings,
        public readonly ?string $detailsUrl = null,
    ) {
    }
}
