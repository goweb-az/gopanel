<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Export;

use App\Support\Export\ExportRegistry;
use App\Support\Export\ExportRenderer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ortaq ixrac endirmə sorğusu: `GET gopanel/exports/download?key=...&format=excel&...süzgəclər`.
 *
 * `key` reyestrdə olmalıdır - sinif adı qəbul edilmir (bax `ExportRegistry`).
 * İcazə açarın öz icazəsidir (reyestrdəki `permission`) - route-a `can:`
 * yazmaq mümkün deyil, çünki icazə açardan asılıdır.
 *
 * Qalan sorğu parametrləri ixracın süzgəcləridir; onların sxemi hər tərifdə
 * fərqlidir, ona görə burada yalnız say və uzunluq məhdudlaşdırılır.
 */
final class ExportDownloadRequest extends FormRequest
{
    /** Süzgəc kimi ötürülməyən texniki açarlar. */
    private const RESERVED = ['key', 'format', '_token', '_'];

    public function authorize(): bool
    {
        $admin = $this->user('gopanel');
        $permission = ExportRegistry::permission((string) $this->input('key'));

        return $admin !== null && ($permission === null || $admin->can($permission));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key'    => ['required', 'string', Rule::in(array_keys(ExportRegistry::all()))],
            'format' => ['nullable', 'string', Rule::in(ExportRenderer::FORMATS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.required' => 'Sənəd növü göstərilməyib.',
            'key.in'       => 'Bu sənəd ixrac edilə bilmir.',
            'format.in'    => 'Format yalnız Excel və ya PDF ola bilər.',
        ];
    }

    public function exportKey(): string
    {
        return (string) $this->validated('key');
    }

    public function exportFormat(): string
    {
        return ExportRenderer::normalizeFormat($this->validated('format'));
    }

    /**
     * Boş və texniki parametrlər atılır; dərinlik 1 səviyyə ilə, say 40 ilə
     * məhdudlaşdırılır - ixrac sorğusu nəhəng massivlə yüklənməsin.
     *
     * @return array<string, string|array<int, string>>
     */
    public function exportParams(): array
    {
        $params = [];

        foreach ($this->query() as $key => $value) {
            if (in_array($key, self::RESERVED, true) || count($params) >= 40) {
                continue;
            }

            if (is_array($value)) {
                $value = array_values(array_filter($value, static fn ($item): bool => is_scalar($item) && $item !== ''));
                if ($value !== []) {
                    $params[(string) $key] = array_map(static fn ($item): string => mb_substr((string) $item, 0, 255), $value);
                }

                continue;
            }

            if (is_scalar($value) && (string) $value !== '') {
                $params[(string) $key] = mb_substr((string) $value, 0, 255);
            }
        }

        return $params;
    }
}
