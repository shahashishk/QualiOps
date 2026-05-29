<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportExportJob;
use App\Models\ExportHistory;
use App\Models\Project;
use App\Models\ScanHistory;
use App\Models\User;
use App\Services\Reports\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_view_reports_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Reports')
            ->assertSee('Export History');
    }

    public function test_report_export_request_creates_history_and_dispatches_job(): void
    {
        Bus::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $project = Project::factory()->create();

        $response = $this->actingAs($admin)->post(route('reports.export'), [
            'report_type' => 'scan_history',
            'format' => 'csv',
            'project_id' => $project->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('export_histories', [
            'user_id' => $admin->id,
            'report_type' => 'scan_history',
            'format' => 'csv',
            'status' => 'queued',
        ]);

        Bus::assertDispatched(GenerateReportExportJob::class);
    }

    public function test_export_job_generates_downloadable_file(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $project = Project::factory()->create();
        ScanHistory::factory()->create([
            'project_id' => $project->id,
            'branch_name' => $project->branch_name,
            'status' => 'completed',
        ]);

        $export = ExportHistory::query()->create([
            'user_id' => $admin->id,
            'report_type' => 'scan_history',
            'filters' => ['project_id' => $project->id],
            'format' => 'csv',
            'file_path' => null,
            'status' => 'queued',
            'generated_at' => null,
        ]);

        app(ReportExportService::class)->generate($export);

        $export->refresh();

        $this->assertSame('completed', $export->status);
        $this->assertNotNull($export->file_path);
        Storage::disk('local')->assertExists($export->file_path);

        $this->actingAs($admin)
            ->get(route('reports.download', $export))
            ->assertOk();
    }
}
