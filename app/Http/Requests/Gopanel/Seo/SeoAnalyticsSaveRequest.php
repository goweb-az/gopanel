<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Seo;

use App\Http\Requests\Gopanel\GopanelFormRequest;

class SeoAnalyticsSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.seo.seo-analytics';

    /** Tək sətirli səhifə - `.add` icazəsi yoxdur. */
    protected function ability(): string
    {
        return $this->module . '.edit';
    }

    /**
     * Sahələr sayta olduğu kimi çap olunan HTML/mətn bloklarıdır (GA skripti,
     * robots.txt məzmunu). Ona görə `string` yoxlamasından başqa məhdudiyyət
     * qoyulmur - `strip_tags` burada məzmunu sıradan çıxarardı.
     */
    public function rules(): array
    {
        return [
            'head'       => ['nullable', 'string'],
            'body'       => ['nullable', 'string'],
            'footer'     => ['nullable', 'string'],
            'robots_txt' => ['nullable', 'string'],
            'ai_txt'     => ['nullable', 'string'],
            'other'      => ['nullable', 'string'],
        ];
    }
}
