<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return $this->user()->can('update', $managedUser);
    }

    public function rules(): array
    {
        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($managedUser->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
