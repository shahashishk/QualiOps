<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Role $role */
        $role = $this->route('role');

        return $this->user()->can('update', $role);
    }

    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }
}
