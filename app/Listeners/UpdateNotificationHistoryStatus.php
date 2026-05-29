<?php

namespace App\Listeners;

use App\Models\NotificationHistory;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Throwable;

class UpdateNotificationHistoryStatus
{
    public function handle(NotificationSent|NotificationFailed $event): void
    {
        $historyId = $event->notification->notificationHistoryId ?? null;

        if (! $historyId) {
            return;
        }

        $history = NotificationHistory::query()->find($historyId);

        if (! $history) {
            return;
        }

        if ($event instanceof NotificationSent) {
            $history->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            return;
        }

        $message = $this->safeFailureMessage($event->data['exception'] ?? null);

        $history->update([
            'status' => 'failed',
            'sent_at' => now(),
            'error_message' => $message,
        ]);
    }

    private function safeFailureMessage(mixed $exception): string
    {
        if (! $exception instanceof Throwable) {
            return 'Notification delivery failed.';
        }

        return str($exception->getMessage())
            ->replaceMatches('/(token|password|secret)=([^\\s&]+)/i', '$1=***')
            ->limit(180)
            ->toString();
    }
}
