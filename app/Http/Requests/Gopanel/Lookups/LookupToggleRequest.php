<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Lookups;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Siyahıdakı keçid düyməsi (`toggle_btn`) - `main.js` `{id, row, status}` göndərir.
 *
 * NİYƏ ümumi `gopanel.general.status.change` işlədilmir: o endpoint istənilən
 * modelin istənilən sütununu icazəsiz dəyişir və lookup qaydalarını
 * (default sətir deaktiv edilməz) bilmir. İcazə route-dadır
 * (`gopanel.can:...edit`), sahənin bayraq olması servisdə yoxlanılır.
 */
class LookupToggleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('gopanel')->check();
    }

    public function rules(): array
    {
        return [
            'id'     => ['required', 'string', 'max:64'],
            'row'    => ['required', 'string', 'max:64'],
            'status' => ['required', 'in:true,false,1,0'],
        ];
    }

    public function value(): bool
    {
        return filter_var($this->input('status'), FILTER_VALIDATE_BOOLEAN);
    }
}
