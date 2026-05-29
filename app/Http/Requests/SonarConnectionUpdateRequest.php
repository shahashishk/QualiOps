<?php

namespace App\Http\Requests;

use App\Models\SonarConnection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SonarConnectionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SonarConnection $sonarConnection */
        $sonarConnection = $this->route('sonar_connection');

        return $this->user()->can('update', $sonarConnection);
    }

    public function rules(): array
    {
        /** @var SonarConnection $sonarConnection */
        $sonarConnection = $this->route('sonar_connection');

        return [
            'connection_name' => ['required', 'string', 'max:255', Rule::unique('sonar_connections', 'connection_name')->ignore($sonarConnection->id)],
            'sonar_url' => ['required', 'url', 'max:255'],
            'token' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $sonarConnection = $this->route('sonar_connection');

            if (blank($this->input('token')) && blank($sonarConnection?->token)) {
                $validator->errors()->add('token', 'Token is required.');
            }
        });
    }
}
