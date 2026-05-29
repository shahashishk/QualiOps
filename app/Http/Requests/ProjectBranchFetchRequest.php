<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectBranchFetchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('projects.create') || $this->user()->can('projects.edit');
    }

    public function rules(): array
    {
        return [
            'git_connection_id' => ['required', 'integer', Rule::exists('git_connections', 'id')],
            'git_url' => ['required', 'url', 'max:255'],
        ];
    }
}
