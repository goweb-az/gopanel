<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Admin;

use App\Services\Gopanel\Admins\AdminPinService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Adminin ÖZ PIN-ini təyin etməsi / geri alması.
 *
 * Cari şifrə soruşulur: sessiya oğurlanıbsa, oğru PIN-i dəyişib qorumanı
 * özünə uyğunlaşdıra bilməsin. İcazə yoxlanmır - hesabın sahibi öz PIN-ini
 * həmişə idarə edir (əks halda icazəsi olmayan admin heç vaxt təsdiq edə bilməzdi).
 *
 * `clear=1` göndəriləndə PIN geri alınır və yeni dəyər gözlənilmir.
 */
class ProfilePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('gopanel')->check();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $pins = app(AdminPinService::class);

        return [
            'clear'            => ['nullable', 'boolean'],
            'current_password' => ['required', 'string', 'current_password:gopanel'],
            'pin'              => [
                Rule::excludeIf($this->boolean('clear')),
                'required',
                'string',
                'regex:/^\d+$/',
                'min:' . $pins->minLength(),
                'max:' . $pins->maxLength(),
                'confirmed',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $pins = app(AdminPinService::class);

        return [
            'current_password.required'         => 'Təsdiq üçün mövcud şifrənizi yazın.',
            'current_password.current_password' => 'Mövcud şifrə düzgün deyil.',
            'pin.required'                      => 'PIN daxil edilməlidir.',
            'pin.regex'                         => 'PIN yalnız rəqəmlərdən ibarət olmalıdır.',
            'pin.min'                           => 'PIN ən az ' . $pins->minLength() . ' rəqəm olmalıdır.',
            'pin.max'                           => 'PIN ən çox ' . $pins->maxLength() . ' rəqəm ola bilər.',
            'pin.confirmed'                     => 'PIN təsdiqi uyğun gəlmir.',
        ];
    }
}
