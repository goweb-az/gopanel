<?php

namespace App\Http\Requests\Gopanel\Translations;

use App\Enums\Gopanel\TranslationGroups;
use App\Enums\Gopanel\TranslationPlatfroms;
use App\Helpers\Gopanel\TranslationPageRegistry;
use App\Models\Geography\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkTranslationImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $locales   = Language::getCachedAll()->pluck('code')->all();
        $groups    = array_column(TranslationGroups::cases(), 'value');
        $platforms = array_column(TranslationPlatfroms::cases(), 'value');

        return [
            'import_type' => ['required', 'string', 'in:json,xlsx'],
            'locale'      => ['required', 'string', 'in:' . implode(',', $locales)],
            'platform'    => ['required', 'string', 'in:' . implode(',', $platforms)],
            'page'        => ['required', 'string', 'max:100'],
            'group'       => ['nullable', 'string', 'in:' . implode(',', $groups)],
            'mode'        => ['required', 'string', 'in:update,skip'],
            'file'        => ['required', 'file', 'max:5120'],
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

            $importType = $this->input('import_type');
            $file       = $this->file('file');

            if ($importType && $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $expected  = $importType === 'xlsx' ? ['xlsx'] : ['json'];

                if (!in_array($extension, $expected, true)) {
                    $validator->errors()->add(
                        'file',
                        "Fayl uzantısı seçilmiş idxal növünə ({$importType}) uyğun deyil."
                    );
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'import_type' => 'İdxal növü',
            'locale'      => 'Dil',
            'platform'    => 'Platforma',
            'page'        => 'Səhifə',
            'group'       => 'Tərcümə faylı',
            'mode'        => 'Rejim',
            'file'        => 'Fayl',
        ];
    }

    public function messages(): array
    {
        return [
            'import_type.required' => 'İdxal növü mütləqdir.',
            'import_type.in'       => 'İdxal növü json və ya xlsx olmalıdır.',
            'locale.required'      => 'Dil mütləqdir.',
            'locale.in'            => 'Seçilmiş dil aktiv deyil.',
            'platform.required'    => 'Platforma mütləqdir.',
            'platform.in'          => 'Seçilmiş platforma mövcud deyil.',
            'page.required'        => 'Səhifə mütləqdir.',
            'group.in'             => 'Seçilmiş tərcümə faylı mövcud deyil.',
            'mode.required'        => 'Rejim mütləqdir.',
            'mode.in'              => 'Rejim update və ya skip olmalıdır.',
            'file.required'        => 'Fayl mütləqdir.',
            'file.max'             => 'Fayl ölçüsü maksimum 5MB ola bilər.',
        ];
    }
}
