<?php

namespace App\Jobs;

use App\Models\ExportHistory;
use App\Services\Reports\ReportExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateReportExportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $exportHistoryId,
    ) {
    }

    public function handle(ReportExportService $reportExportService): void
    {
        $exportHistory = ExportHistory::query()->find($this->exportHistoryId);

        if (! $exportHistory) {
            return;
        }

        $reportExportService->generate($exportHistory);
    }
}
