<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Lookups;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sürüklə-burax sırası: `orders = [uid, uid, ...]` yeni ardıcıllıqla.
 *
 * İcazə route middleware-indədir (`gopanel.can:gopanel.lookups.{tip}.sort`).
 * Açarların cədvəldə mövcudluğu `LookupService::sort()`-da TƏK sorğu ilə
 * yoxlanılır - burada `exists` qaydası hər element üçün ayrıca sorğu edərdi.
 */
class LookupSortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('gopanel')->check();
    }

    public function rules(): array
    {
        return [
            'orders'   => ['required', 'array', 'min:1', 'max:500'],
            'orders.*' => ['required', 'string', 'max:64', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'orders.required'   => 'Sıralama məlumatı göndərilməyib.',
            'orders.*.distinct' => 'Eyni sətir sıralamada təkrarlana bilməz.',
        ];
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_values(array_map('strval', (array) $this->validated('orders')));
    }
}
