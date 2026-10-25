<?php

namespace App\Http\Requests\Gopanel\Translations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tərcümələrin Excel idxalı. Route-da `can:` var; burada da eyni icazə yoxlanılır
 * ki, route siyahısı dəyişəndə müdafiə itməsin (01-umumi.md §5).
 */
class TranslationExcelImportRequest extends FormRequest
{
    public const MODE_MISSING   = 'missing';
    public const MODE_OVERWRITE = 'overwrite';

    public function authorize(): bool
    {
        return (bool) auth('gopanel')->user()?->can('gopanel.settings.translations.import');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            'mode' => ['required', 'in:' . self::MODE_MISSING . ',' . self::MODE_OVERWRITE],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Excel faylını seçin.',
            'file.mimes'    => 'Yalnız .xlsx faylı qəbul olunur - ixrac edilmiş və ya nümunə faylı işlədin.',
            'file.max'      => 'Fayl 10 MB-dan böyük ola bilməz.',
            'mode.in'       => 'İdxal rejimi düzgün deyil.',
        ];
    }

    public function overwrite(): bool
    {
        return $this->input('mode') === self::MODE_OVERWRITE;
    }
}
