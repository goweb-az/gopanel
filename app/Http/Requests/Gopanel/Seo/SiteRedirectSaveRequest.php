<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Seo;

use App\Enums\Gopanel\Seo\RedirectMatchTypeEnum;
use App\Http\Requests\Gopanel\GopanelFormRequest;
use Illuminate\Validation\Rule;

class SiteRedirectSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.seo.site-redirects';

    public function rules(): array
    {
        return [
            'locale'      => ['nullable', 'string', 'max:10'],
            'source'      => ['required', 'string', 'max:2048'],
            'match_type'  => ['required', Rule::in(array_column(RedirectMatchTypeEnum::cases(), 'value'))],
            'regex_flags' => ['nullable', 'string', 'max:10'],
            'target'      => ['required', 'string', 'max:2048'],
            // 301/302 ən çox işlədilənlərdir; 307/308 metodu qoruyan variantlardır
            'http_code'   => ['required', 'in:301,302,307,308'],
            'is_active'   => ['nullable', 'in:0,1'],
            'priority'    => ['nullable', 'integer'],
            'starts_at'   => ['nullable', 'date'],
            'ends_at'     => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'source'     => 'Mənbə ünvan',
            'target'     => 'Hədəf ünvan',
            'match_type' => 'Uyğunluq tipi',
            'http_code'  => 'HTTP kodu',
        ];
    }
}
