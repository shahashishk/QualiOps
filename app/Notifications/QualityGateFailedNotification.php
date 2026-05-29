<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\SonarMetricSnapshot;
use App\Notifications\Concerns\TracksNotificationHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QualityGateFailedNotification extends Notification implements ShouldQueue
{
    use Queueable, TracksNotificationHistory;

    public function __construct(
        private readonly Project $project,
        private readonly SonarMetricSnapshot $snapshot,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Quality Gate Failed: '.$this->project->project_name)
            ->markdown('emails.quality-gate-failed', [
                'project' => $this->project,
                'snapshot' => $this->snapshot,
                'portalUrl' => route('projects.show', $this->project),
                'sonarUrl' => $this->project->sonarConnection
                    ? rtrim((string) $this->project->sonarConnection->sonar_url, '/').'/dashboard?id='.$this->project->sonar_project_key
                    : null,
            ]);
    }
}
