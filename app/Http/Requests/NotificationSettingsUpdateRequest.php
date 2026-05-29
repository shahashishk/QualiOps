<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('notifications.manage');
    }

    public function rules(): array
    {
        return [
            'email_enabled' => ['nullable', 'boolean'],
            'notify_on_scan_success' => ['nullable', 'boolean'],
            'notify_on_scan_failed' => ['nullable', 'boolean'],
            'notify_on_quality_gate_failed' => ['nullable', 'boolean'],
            'notify_on_scheduler_failed' => ['nullable', 'boolean'],
            'notify_on_worker_down' => ['nullable', 'boolean'],
        ];
    }
}
