<?php

namespace App\Http\Requests\Gopanel\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('gopanel')->check();
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!Hash::check($value, Auth::guard('gopanel')->user()->password)) {
                    $fail('Mövcud şifrə yanlışdır.');
                }
            }],
            'password'         => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'Mövcud şifrə',
            'password'         => 'Yeni şifrə',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Mövcud şifrəni daxil edin.',
            'password.required'         => 'Yeni şifrəni daxil edin.',
            'password.min'              => 'Yeni şifrə ən az 6 simvol olmalıdır.',
            'password.confirmed'        => 'Şifrə təsdiqi uyğun gəlmir.',
        ];
    }
}
