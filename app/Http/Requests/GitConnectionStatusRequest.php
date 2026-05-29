<?php

namespace App\Http\Requests;

use App\Models\GitConnection;
use Illuminate\Foundation\Http\FormRequest;

class GitConnectionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var GitConnection $gitConnection */
        $gitConnection = $this->route('git_connection');

        return $this->user()->can('update', $gitConnection);
    }

    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
        ];
    }
}
