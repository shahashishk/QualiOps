<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return $this->user()->can('delete', $managedUser);
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
