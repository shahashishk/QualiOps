<?php

namespace App\Listeners;

use App\Events\QualityGateFailedEvent;
use App\Events\ScanLifecycleEvent;
use App\Events\SystemWarningEvent;
use App\Services\Notification\NotificationDispatchService;

class SendGovernanceNotifications
{
    public function __construct(
        private readonly NotificationDispatchService $notificationDispatchService,
    ) {
    }

    public function handle(object $event): void
    {
        match (true) {
            $event instanceof ScanLifecycleEvent && in_array($event->eventType, ['scan_completed', 'scheduled_scan_completed'], true)
                => $this->notificationDispatchService->sendScanCompleted($event->project, $event->scanHistory, $event->scheduled),
            $event instanceof ScanLifecycleEvent && in_array($event->eventType, ['scan_failed', 'scheduled_scan_failed'], true)
                => $this->notificationDispatchService->sendScanFailed($event->project, $event->scanHistory, $event->scheduled),
            $event instanceof QualityGateFailedEvent
                => $this->notificationDispatchService->sendQualityGateFailed($event->project, $event->snapshot),
            $event instanceof SystemWarningEvent
                => $this->notificationDispatchService->sendSystemWarning($event->eventType, $event->warnings, $event->detailsUrl),
            default => null,
        };
    }
}
