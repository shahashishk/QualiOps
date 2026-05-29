<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\ScanHistory;
use App\Notifications\Concerns\TracksNotificationHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScanSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable, TracksNotificationHistory;

    public function __construct(
        private readonly Project $project,
        private readonly ScanHistory $scanHistory,
        private readonly bool $scheduled = false,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(($this->scheduled ? '[Scheduled] ' : '').'Scan Completed: '.$this->project->project_name)
            ->markdown('emails.scan-success', [
                'project' => $this->project,
                'history' => $this->scanHistory,
                'scheduled' => $this->scheduled,
                'portalUrl' => route('scan-history.show', $this->scanHistory),
                'sonarUrl' => $this->scanHistory->sonar_dashboard_url,
            ]);
    }
}
