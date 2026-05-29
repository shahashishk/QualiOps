<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class ProjectStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $this->user()->can('delete', $project);
    }

    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
        ];
    }
}
