<?php

namespace App\Http\Requests\Gopanel\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($this->route('item')?->id)
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Rol adı',
            'permissions' => 'İcazələr',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Rol adı boş buraxıla bilməz.',
            'name.unique' => 'Bu rol adı artıq istifadə olunur.',
            'permissions.*.exists' => 'Seçilmiş icazələrdən bəziləri mövcud deyil.',
        ];
    }
}
