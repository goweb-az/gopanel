<?php

namespace App\Http\Requests\Gopanel\Translations;

use App\Enums\Gopanel\TranslationPlatfroms;
use App\Helpers\Gopanel\TranslationPageRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ExportTranslationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $platforms = array_column(TranslationPlatfroms::cases(), 'value');

        return [
            'platform' => ['nullable', 'string', 'in:' . implode(',', $platforms)],
            'page'     => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $platform = $this->input('platform');
            $page     = $this->input('page');

            if ($platform && $page && !app(TranslationPageRegistry::class)->exists($platform, $page)) {
                $validator->errors()->add('page', 'Seçilmiş səhifə seçilmiş platformaya aid deyil.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'platform' => 'Platforma',
            'page'     => 'Səhifə',
        ];
    }

    public function messages(): array
    {
        return [
            'platform.in' => 'Seçilmiş platforma mövcud deyil.',
        ];
    }
}
