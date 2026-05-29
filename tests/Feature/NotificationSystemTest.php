<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ScanHistory;
use App\Models\User;
use App\Notifications\QualityGateFailedNotification as QualityGateFailedNotificationMessage;
use App\Notifications\ScanSuccessNotification;
use App\Services\Notification\NotificationDispatchService;
use App\Services\System\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_view_notification_settings_and_history(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)
            ->get(route('notifications.settings.edit'))
            ->assertOk()
            ->assertSee('Notification Settings');

        $this->actingAs($admin)
            ->get(route('notifications.history.index'))
            ->assertOk()
            ->assertSee('Notification History');
    }

    public function test_scan_success_notification_is_dispatched_to_project_recipients(): void
    {
        Notification::fake();

        app(SystemSettingsService::class)->set('email_enabled', true);
        app(SystemSettingsService::class)->set('notify_on_scan_success', true);

        $pm = User::factory()->create(['email' => 'pm@example.com']);
        $pm->assignRole('PM');

        $project = Project::factory()->create([
            'notify_pm' => true,
            'notification_custom_emails' => 'custom@example.com',
        ]);

        $history = ScanHistory::factory()->create([
            'project_id' => $project->id,
            'branch_name' => $project->branch_name,
            'scan_type' => $project->scan_type,
            'status' => 'completed',
        ]);

        app(NotificationDispatchService::class)->sendScanCompleted($project->fresh(), $history);

        Notification::assertSentTo($pm, ScanSuccessNotification::class);
        Notification::assertSentOnDemand(ScanSuccessNotification::class);

        $this->assertDatabaseHas('notification_histories', [
            'project_id' => $project->id,
            'scan_history_id' => $history->id,
            'event_type' => 'scan_completed',
            'recipient' => 'pm@example.com',
            'status' => 'queued',
        ]);
    }

    public function test_quality_gate_failed_notification_is_dispatched_when_enabled(): void
    {
        Notification::fake();

        app(SystemSettingsService::class)->set('email_enabled', true);
        app(SystemSettingsService::class)->set('notify_on_quality_gate_failed', true);

        $tl = User::factory()->create(['email' => 'tl@example.com']);
        $tl->assignRole('TL');

        $project = Project::factory()->create([
            'notify_tl' => true,
        ]);

        $snapshot = $project->sonarMetricSnapshots()->create([
            'sonar_project_key' => $project->sonar_project_key,
            'bugs' => 1,
            'vulnerabilities' => 2,
            'code_smells' => 3,
            'security_hotspots' => 0,
            'coverage' => 55.5,
            'duplicated_lines_density' => 1.2,
            'reliability_rating' => 'B',
            'security_rating' => 'C',
            'maintainability_rating' => 'D',
            'technical_debt' => 12,
            'quality_gate_status' => 'ERROR',
            'synced_at' => now(),
        ]);

        app(NotificationDispatchService::class)->sendQualityGateFailed($project->fresh(['sonarConnection']), $snapshot);

        Notification::assertSentTo($tl, QualityGateFailedNotificationMessage::class);

        $this->assertDatabaseHas('notification_histories', [
            'project_id' => $project->id,
            'scan_history_id' => null,
            'event_type' => 'quality_gate_failed',
            'recipient' => 'tl@example.com',
            'status' => 'queued',
        ]);
    }

    public function test_duplicate_scan_success_notifications_are_suppressed_within_window(): void
    {
        Notification::fake();

        app(SystemSettingsService::class)->set('email_enabled', true);
        app(SystemSettingsService::class)->set('notify_on_scan_success', true);

        $pm = User::factory()->create(['email' => 'pm@example.com']);
        $pm->assignRole('PM');

        $project = Project::factory()->create([
            'notify_pm' => true,
        ]);

        $history = ScanHistory::factory()->create([
            'project_id' => $project->id,
            'branch_name' => $project->branch_name,
            'scan_type' => $project->scan_type,
            'status' => 'completed',
        ]);

        $service = app(NotificationDispatchService::class);
        $service->sendScanCompleted($project->fresh(), $history);
        $service->sendScanCompleted($project->fresh(), $history);

        Notification::assertSentToTimes($pm, ScanSuccessNotification::class, 1);
        $this->assertDatabaseCount('notification_histories', 1);
    }
}
