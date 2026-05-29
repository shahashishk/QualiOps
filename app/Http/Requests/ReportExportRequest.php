<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reports.export');
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in([
                'project_quality_summary',
                'scan_history',
                'failed_scans',
                'quality_gate_failed',
                'metrics_snapshot',
            ])],
            'format' => ['required', 'string', Rule::in(['csv', 'xls'])],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'branch' => ['nullable', 'string', 'max:255'],
            'scan_status' => ['nullable', 'string', Rule::in(['pending', 'running', 'completed', 'failed', 'cancelled'])],
            'quality_gate_status' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'triggered_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }
}
