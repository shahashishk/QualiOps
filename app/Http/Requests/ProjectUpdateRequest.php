<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $this->user()->can('update', $project);
    }

    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'project_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'git_connection_id' => ['required', 'integer', Rule::exists('git_connections', 'id')],
            'git_url' => ['required', 'url', 'max:255'],
            'branch_name' => ['required', 'string', 'max:255'],
            'sonar_connection_id' => ['required', 'integer', Rule::exists('sonar_connections', 'id')],
            'sonar_project_key' => ['required', 'string', 'max:255', Rule::unique('projects', 'sonar_project_key')->ignore($project->id)],
            'sonar_project_name' => ['required', 'string', 'max:255'],
            'scan_type' => ['required', 'string', Rule::in(['auto', 'dotnet', 'angular', 'flutter', 'node', 'generic', 'mixed'])],
            'schedule_enabled' => ['nullable', 'boolean'],
            'schedule_frequency' => ['required', 'string', Rule::in(['manual', 'daily', 'weekly'])],
            'schedule_day' => ['nullable', 'string', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'schedule_time' => ['nullable', 'date_format:H:i'],
            'active' => ['nullable', 'boolean'],
            'notify_pm' => ['nullable', 'boolean'],
            'notify_tl' => ['nullable', 'boolean'],
            'notify_qa' => ['nullable', 'boolean'],
            'notify_developer' => ['nullable', 'boolean'],
            'notification_custom_emails' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('schedule_enabled') && blank($this->input('schedule_time'))) {
                $validator->errors()->add('schedule_time', 'Schedule time is required when scheduling is enabled.');
            }

            if ($this->boolean('schedule_enabled')
                && $this->input('schedule_frequency') === 'weekly'
                && blank($this->input('schedule_day'))) {
                $validator->errors()->add('schedule_day', 'Schedule day is required for weekly schedules.');
            }

            $emails = preg_split('/[\s,;]+/', (string) $this->input('notification_custom_emails'), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($emails as $email) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $validator->errors()->add('notification_custom_emails', 'Each custom notification email must be a valid email address.');
                    break;
                }
            }
        });
    }
}
