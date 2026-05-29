<?php

namespace App\Services\Reports;

use App\Models\ExportHistory;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ReportExportService
{
    public function __construct(
        private readonly ReportDataService $reportDataService,
    ) {
    }

    public function generate(ExportHistory $exportHistory): ExportHistory
    {
        $exportHistory->update([
            'status' => 'running',
            'file_path' => null,
            'generated_at' => null,
        ]);

        try {
            /** @var User $user */
            $user = $exportHistory->user()->firstOrFail();
            $report = $this->reportDataService->build($exportHistory->report_type, $exportHistory->filters ?? [], $user);
            $extension = $exportHistory->format === 'xls' ? 'xls' : 'csv';
            $relativePath = 'exports/'.now()->format('Y/m').'/'.Str::uuid().'-'.$exportHistory->report_type.'.'.$extension;

            Storage::disk('local')->put(
                $relativePath,
                $exportHistory->format === 'xls'
                    ? $this->excelContent($report['title'], $report['headers'], $report['rows'])
                    : $this->csvContent($report['headers'], $report['rows'])
            );

            $exportHistory->update([
                'status' => 'completed',
                'file_path' => $relativePath,
                'generated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $exportHistory->update([
                'status' => 'failed',
                'generated_at' => now(),
            ]);

            throw $exception;
        }

        return $exportHistory->fresh();
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<int, string|int|float|null>> $rows
     */
    private function csvContent(array $headers, array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $headers);

        foreach ($rows as $row) {
            fputcsv($stream, array_map(fn ($value) => $value ?? '', $row));
        }

        rewind($stream);
        $content = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $content;
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<int, string|int|float|null>> $rows
     */
    private function excelContent(string $title, array $headers, array $rows): string
    {
        $thead = collect($headers)->map(fn (string $header) => '<th>'.e($header).'</th>')->implode('');
        $tbody = collect($rows)->map(function (array $row) {
            return '<tr>'.collect($row)->map(fn ($value) => '<td>'.e((string) ($value ?? '')).'</td>')->implode('').'</tr>';
        })->implode('');

        return '<html><head><meta charset="utf-8"><title>'.e($title).'</title></head><body><table border="1"><thead><tr>'.$thead.'</tr></thead><tbody>'.$tbody.'</tbody></table></body></html>';
    }
}
