<?php

namespace App\Http\Requests\Gopanel\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('gopanel')->check();
    }

    public function rules(): array
    {
        $adminId = Auth::guard('gopanel')->id();

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:admins,email,' . $adminId],
            'image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'Ad soyad',
            'email'     => 'E-poçt',
            'image'     => 'Profil şəkli',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Ad soyad mütləqdir.',
            'full_name.max'      => 'Ad soyad maksimum 255 simvol ola bilər.',
            'email.required'     => 'E-poçt mütləqdir.',
            'email.email'        => 'E-poçt düzgün formatda deyil.',
            'email.unique'       => 'Bu e-poçt artıq istifadə olunub.',
            'image.image'        => 'Fayl şəkil formatında olmalıdır.',
            'image.mimes'        => 'Şəkil yalnız jpg, jpeg, png, webp formatında ola bilər.',
            'image.max'          => 'Şəkil maksimum 2MB ola bilər.',
        ];
    }
}
