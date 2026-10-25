<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Həssas əməliyyatdan əvvəl PIN təsdiqi (modal və ya ayrıca səhifə).
 *
 * Burada yalnız forma yoxlanır; PIN-in düzgünlüyü və cəhd limiti
 * `AdminPinService::attemptConfirmation()`-dadır - limit validasiya
 * xətasından əvvəl yox, PIN müqayisəsindən əvvəl işləməlidir.
 */
class AdminPinConfirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('gopanel')->check();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'pin' => ['required', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pin.required' => 'PIN daxil edilməlidir.',
            'pin.max'      => 'PIN səhvdir.',
        ];
    }
}
