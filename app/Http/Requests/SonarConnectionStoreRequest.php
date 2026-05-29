<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SonarConnectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sonar_connections.manage');
    }

    public function rules(): array
    {
        return [
            'connection_name' => ['required', 'string', 'max:255', Rule::unique('sonar_connections', 'connection_name')],
            'sonar_url' => ['required', 'url', 'max:255'],
            'token' => ['required', 'string'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
