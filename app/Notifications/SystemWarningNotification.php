<?php

namespace App\Notifications;

use App\Notifications\Concerns\TracksNotificationHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemWarningNotification extends Notification implements ShouldQueue
{
    use Queueable, TracksNotificationHistory;

    /**
     * @param array<int, string> $warnings
     */
    public function __construct(
        private readonly string $eventType,
        private readonly array $warnings,
        private readonly ?string $detailsUrl = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('System Warning: '.str($this->eventType)->replace(['_', '.'], ' ')->title())
            ->markdown('emails.system-warning', [
                'eventType' => $this->eventType,
                'warnings' => $this->warnings,
                'detailsUrl' => $this->detailsUrl,
            ]);
    }
}
