<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GitConnectionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('git_connections.manage');
    }

    public function rules(): array
    {
        return [
            'connection_name' => ['required', 'string', 'max:255', Rule::unique('git_connections', 'connection_name')],
            'provider_type' => ['required', 'string', Rule::in(['gitlab', 'github', 'bitbucket', 'generic'])],
            'base_url' => ['required', 'url', 'max:255'],
            'auth_type' => ['required', 'string', Rule::in(['token', 'username_password', 'ssh'])],
            'username' => ['nullable', 'string', 'max:255'],
            'token' => ['nullable', 'string'],
            'ssh_private_key' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $authType = $this->input('auth_type');

            if (in_array($authType, ['token', 'username_password'], true) && blank($this->input('token'))) {
                $validator->errors()->add('token', 'A token or password is required for the selected authentication type.');
            }

            if ($authType === 'username_password' && blank($this->input('username'))) {
                $validator->errors()->add('username', 'Username is required for username/password authentication.');
            }

            if ($authType === 'ssh' && blank($this->input('ssh_private_key'))) {
                $validator->errors()->add('ssh_private_key', 'SSH private key is required for SSH authentication.');
            }
        });
    }
}
