<?php

namespace App\Http\Requests;

use App\Models\SonarConnection;
use Illuminate\Foundation\Http\FormRequest;

class SonarConnectionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SonarConnection $sonarConnection */
        $sonarConnection = $this->route('sonar_connection');

        return $this->user()->can('update', $sonarConnection);
    }

    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
        ];
    }
}
