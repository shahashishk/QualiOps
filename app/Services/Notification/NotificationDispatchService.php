<?php

namespace App\Services\Notification;

use App\Models\NotificationHistory;
use App\Models\Project;
use App\Models\ScanHistory;
use App\Models\User;
use App\Notifications\QualityGateFailedNotification;
use App\Notifications\ScanFailedNotification;
use App\Notifications\ScanSuccessNotification;
use App\Notifications\SystemWarningNotification;
use App\Services\System\SystemSettingsService;
use Illuminate\Notifications\AnonymousNotifiable;
use Throwable;

class NotificationDispatchService
{
    public function __construct(
        private readonly ProjectNotificationRecipientService $recipientService,
        private readonly SystemSettingsService $systemSettingsService,
    ) {
    }

    public function sendScanCompleted(Project $project, ScanHistory $scanHistory, bool $scheduled = false): void
    {
        if (! $this->systemSettingsService->emailEnabled() || ! $this->systemSettingsService->notifyOnScanSuccess()) {
            return;
        }

        $this->sendProjectNotification(
            project: $project,
            scanHistory: $scanHistory,
            eventType: $scheduled ? 'scheduled_scan_completed' : 'scan_completed',
            notificationFactory: fn () => new ScanSuccessNotification($project, $scanHistory, $scheduled),
        );
    }

    public function sendScanFailed(Project $project, ScanHistory $scanHistory, bool $scheduled = false): void
    {
        if (! $this->systemSettingsService->emailEnabled() || ! $this->systemSettingsService->notifyOnScanFailed()) {
            return;
        }

        $this->sendProjectNotification(
            project: $project,
            scanHistory: $scanHistory,
            eventType: $scheduled ? 'scheduled_scan_failed' : 'scan_failed',
            notificationFactory: fn () => new ScanFailedNotification($project, $scanHistory, $scheduled),
        );
    }

    public function sendQualityGateFailed(Project $project, object $snapshot): void
    {
        if (! $this->systemSettingsService->emailEnabled() || ! $this->systemSettingsService->notifyOnQualityGateFailed()) {
            return;
        }

        $this->sendProjectNotification(
            project: $project,
            scanHistory: null,
            eventType: 'quality_gate_failed',
            notificationFactory: fn () => new QualityGateFailedNotification($project, $snapshot),
        );
    }

    /**
     * @param array<int, string> $warnings
     */
    public function sendSystemWarning(string $eventType, array $warnings, ?string $detailsUrl = null): void
    {
        if (! $this->systemSettingsService->emailEnabled()) {
            return;
        }

        if ($eventType === 'worker_down' && ! $this->systemSettingsService->notifyOnWorkerDown()) {
            return;
        }

        if ($eventType === 'scheduler_failed' && ! $this->systemSettingsService->notifyOnSchedulerFailed()) {
            return;
        }

        $users = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Super Admin', 'Admin']))
            ->orderBy('email')
            ->get();

        foreach ($users as $user) {
            $history = $this->createHistory(null, null, $eventType, $user->email);

            if (! $history) {
                continue;
            }

            try {
                $user->notify((new SystemWarningNotification($eventType, $warnings, $detailsUrl))->withHistoryId($history->id));
            } catch (Throwable $exception) {
                $this->markHistoryFailed($history, $exception);
            }
        }
    }

    /**
     * @param callable(): object $notificationFactory
     */
    private function sendProjectNotification(Project $project, ?ScanHistory $scanHistory, string $eventType, callable $notificationFactory): void
    {
        $recipients = $this->recipientService->recipients($project);

        foreach ($recipients['users'] as $user) {
            $history = $this->createHistory($project, $scanHistory, $eventType, $user->email);

            if (! $history) {
                continue;
            }

            try {
                $user->notify($notificationFactory()->withHistoryId($history->id));
            } catch (Throwable $exception) {
                $this->markHistoryFailed($history, $exception);
            }
        }

        foreach ($recipients['emails'] as $email) {
            $history = $this->createHistory($project, $scanHistory, $eventType, $email);

            if (! $history) {
                continue;
            }

            try {
                $anonymous = new AnonymousNotifiable();
                $anonymous->route('mail', $email)->notify($notificationFactory()->withHistoryId($history->id));
            } catch (Throwable $exception) {
                $this->markHistoryFailed($history, $exception);
            }
        }
    }

    private function createHistory(?Project $project, ?ScanHistory $scanHistory, string $eventType, string $recipient): ?NotificationHistory
    {
        $existing = NotificationHistory::query()
            ->where('project_id', $project?->id)
            ->where('scan_history_id', $scanHistory?->id)
            ->where('event_type', $eventType)
            ->where('recipient', $recipient)
            ->whereIn('status', ['queued', 'sent'])
            ->where('created_at', '>=', now()->subHours(6))
            ->exists();

        if ($existing) {
            return null;
        }

        return NotificationHistory::query()->create([
            'project_id' => $project?->id,
            'scan_history_id' => $scanHistory?->id,
            'event_type' => $eventType,
            'recipient' => $recipient,
            'status' => 'queued',
            'sent_at' => null,
            'error_message' => null,
        ]);
    }

    private function markHistoryFailed(NotificationHistory $history, Throwable $exception): void
    {
        $message = str($exception->getMessage())
            ->replaceMatches('/(token|password|secret)=([^\\s&]+)/i', '$1=***')
            ->limit(180)
            ->toString();

        $history->update([
            'status' => 'failed',
            'sent_at' => now(),
            'error_message' => $message,
        ]);
    }
}
