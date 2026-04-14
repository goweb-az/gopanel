<?php

namespace App\Http\Requests\Gopanel\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Yalnız şifrə dəyişdirmə sorğusu
        if ($this->_change_password_only) {
            return [
                'password' => ['required', 'string', 'min:6', 'confirmed'],
            ];
        }

        $isUpdate = $this->route('item') ? true : false;

        $rules = [
            'full_name'  => ['required', 'string', 'max:255'],
            'role'       => ['nullable', 'exists:roles,id'],
            'is_super'   => ['required', 'in:0,1'],
            'is_active'  => ['required', 'in:0,1'],
            'image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($isUpdate) {
            // Update zamanı email disabled olduğu üçün göndərilmir
            $rules['email'] = ['nullable', 'email', 'max:255', 'unique:users,email,' . $this->route('item')];
        } else {
            // Create zamanı email + password mütləqdir
            $rules['email']    = ['required', 'email', 'max:255', 'unique:users,email'];
            $rules['password'] = ['required', 'string', 'min:6', 'confirmed'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'full_name'  => 'Ad soyad',
            'email'     => 'E-poçt',
            'password'  => 'Şifrə',
            'role'      => 'Vəzifə',
            'is_super'  => 'Super admin',
            'is_active' => 'Status',
        ];
    }


    public function messages(): array
    {
        return [
            'full_name.required'  => 'Ad soyad mütləqdir.',
            'full_name.max'       => 'Ad soyad maksimum 255 simvol ola bilər.',
            'email.required'     => 'E-poçt mütləqdir.',
            'email.email'        => 'E-poçt düzgün formatda deyil.',
            'email.unique'       => 'Bu e-poçt artıq istifadə olunub.',
            'password.required'  => 'Şifrə mütləqdir.',
            'password.min'       => 'Şifrə ən az 6 simvol olmalıdır.',
            'password.confirmed' => 'Şifrə təsdiqi uyğun gəlmir.',
            'role.required'      => 'Vəzifə seçilməlidir.',
            'role.exists'        => 'Seçilmiş vəzifə mövcud deyil.',
            'is_super.required'  => 'Super admin seçimi mütləqdir.',
            'is_super.in'        => 'Super admin dəyəri yalnız 0 və ya 1 ola bilər.',
            'is_active.required' => 'Status seçimi mütləqdir.',
            'is_active.in'       => 'Status dəyəri yalnız 0 və ya 1 ola bilər.',
        ];
    }
}
