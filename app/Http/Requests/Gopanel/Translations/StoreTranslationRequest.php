<?php

namespace App\Http\Requests\Gopanel\Translations;

use App\Enums\Gopanel\TranslationGroups;
use App\Enums\Gopanel\TranslationPlatfroms;
use App\Helpers\Gopanel\TranslationPageRegistry;
use App\Models\Geography\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $locales = Language::getCachedAll()->pluck('code')->all();
        $groups  = array_column(TranslationGroups::cases(), 'value');
        $platforms = array_column(TranslationPlatfroms::cases(), 'value');

        $rules = [
            'key'      => ['required', 'string', 'max:191'],
            'group'    => ['nullable', 'string', 'in:' . implode(',', $groups)],
            'platform' => ['required', 'string', 'in:' . implode(',', $platforms)],
            'page'     => ['nullable', 'string', 'max:100'],
            'filename' => ['nullable', 'string', 'max:100'],
            'value'    => ['nullable', 'array'],
        ];

        foreach ($locales as $code) {
            $rules["value.{$code}"] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $platform = $this->input('platform');
            $page     = $this->input('page') ?: 'general';

            if (!app(TranslationPageRegistry::class)->exists($platform, $page)) {
                $validator->errors()->add('page', 'Seçilmiş səhifə seçilmiş platformaya aid deyil.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'key'      => 'Açar',
            'group'    => 'Tərcümə faylı',
            'platform' => 'Platforma',
            'page'     => 'Səhifə',
            'filename' => 'Fayl adı',
            'value'    => 'Dəyər',
        ];
    }

    public function messages(): array
    {
        return [
            'key.required'      => 'Açar mütləqdir.',
            'group.in'          => 'Seçilmiş tərcümə faylı mövcud deyil.',
            'platform.required' => 'Platforma mütləqdir.',
            'platform.in'       => 'Seçilmiş platforma mövcud deyil.',
        ];
    }
}
