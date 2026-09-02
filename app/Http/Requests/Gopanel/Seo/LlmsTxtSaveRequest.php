<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Seo;

use App\Http\Requests\Gopanel\GopanelFormRequest;

class LlmsTxtSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.seo.llms-txt';

    /** Tək sətirli səhifə - `.add` icazəsi yoxdur. */
    protected function ability(): string
    {
        return $this->module . '.edit';
    }

    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string'],
        ];
    }
}
