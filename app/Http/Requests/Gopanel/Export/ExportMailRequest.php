<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Export;

use App\Support\Export\ExportRegistry;
use App\Support\Export\ExportRenderer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * İxracın mail ilə göndərilməsi sorğusu (`POST gopanel/exports/mail`).
 *
 * `key` reyestrdə olmalıdır - sinif adı qəbul edilmir (bax `ExportRegistry`).
 * Açarın icazəsi `ExportMailService::authorize()`-də yoxlanılır ki, mail və
 * endirmə eyni yerdən qorunsun; burada yalnız login yoxlanılır.
 *
 * `params` endirmə linkinə gedən süzgəclərlə eynidir; sxem tərifdən asılıdır,
 * ona görə yalnız say və uzunluq məhdudlaşdırılır - növbəyə nəhəng massiv
 * düşməsin.
 */
final class ExportMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('gopanel') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key'      => ['required', 'string', Rule::in(ExportRegistry::mailableKeys())],
            'format'   => ['required', 'string', Rule::in(ExportRenderer::FORMATS)],
            'email'    => ['required', 'email:rfc', 'max:190'],
            'note'     => ['nullable', 'string', 'max:500'],
            'params'   => ['nullable', 'array', 'max:40'],
            'params.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.required'   => 'Sənəd növü göstərilməyib.',
            'key.in'         => 'Bu sənəd mail ilə göndərilə bilmir.',
            'format.required' => 'Format seçilməyib.',
            'format.in'      => 'Format yalnız Excel və ya PDF ola bilər.',
            'email.required' => 'E-poçt ünvanı yazılmalıdır.',
            'email.email'    => 'E-poçt ünvanı düzgün deyil.',
            'email.max'      => 'E-poçt ünvanı çox uzundur.',
            'note.max'       => 'Qeyd 500 simvoldan uzun ola bilməz.',
            'params.max'     => 'Süzgəclərin sayı həddindən çoxdur.',
            'params.*.max'   => 'Süzgəc dəyəri çox uzundur.',
        ];
    }

    /**
     * Boş süzgəclər növbəyə düşməsin - payload kiçik, jurnal oxunaqlı qalsın.
     *
     * @return array<string, string>
     */
    public function exportParams(): array
    {
        /** @var array<string, string|null> $params */
        $params = $this->validated('params') ?? [];

        return array_filter(
            $params,
            static fn ($value): bool => is_string($value) && $value !== '',
        );
    }
}
