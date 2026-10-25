<?php

declare(strict_types=1);

namespace App\Support\Export;

use Illuminate\Support\Facades\Route;

/**
 * İxrac düymələri üçün hazır linklər və format siyahısı.
 *
 * NİYƏ: `gopanel.exports.buttons` partial-ında `@php` yazılmır (01-umumi.md § 3) -
 * URL yığmaq, Dompdf-un olub-olmadığını yoxlamaq, ikon seçmək burada olur,
 * blade yalnız çap edir.
 */
final class ExportLinks
{
    private const FORMAT_META = [
        'excel' => ['label' => 'Excel (.xlsx)', 'icon' => 'bx bx-spreadsheet text-success'],
        'pdf'   => ['label' => 'PDF', 'icon' => 'bx bxs-file-pdf text-danger'],
    ];

    /**
     * Ortaq endirmə endpoint-inin linki.
     *
     * @param  array<string, mixed>  $params  sabit süzgəclər (cari süzgəcləri JS klik anında əlavə edir)
     */
    public static function download(string $key, string $format = 'excel', array $params = []): string
    {
        if (!Route::has('gopanel.exports.download')) {
            return '#';
        }

        return route('gopanel.exports.download', array_merge($params, [
            'key'    => $key,
            'format' => ExportRenderer::normalizeFormat($format),
        ]));
    }

    public static function mailUrl(): string
    {
        return Route::has('gopanel.exports.mail') ? route('gopanel.exports.mail') : '';
    }

    /**
     * Bu serverdə işləyən formatlar - Dompdf yoxdursa PDF bəndi göstərilmir.
     *
     * @return array<int, array{value: string, label: string, icon: string}>
     */
    public static function formats(): array
    {
        return array_map(
            static fn (string $format): array => ['value' => $format] + self::FORMAT_META[$format],
            ExportRenderer::availableFormats(),
        );
    }

    /** Mail pəncərəsindəki format kartları üçün JSON (`data-*` atributuna). */
    public static function formatsJson(): string
    {
        return (string) json_encode(array_map(
            static fn (array $format): array => [
                'value' => $format['value'],
                'label' => ExportRenderer::formatLabel($format['value']),
                'icon'  => $format['icon'],
            ],
            self::formats(),
        ), JSON_UNESCAPED_UNICODE);
    }

    /** Açar mailə göndərilə bilərmi və mail route-u varmı. */
    public static function canMail(string $key): bool
    {
        return ExportRegistry::mailable($key) && self::mailUrl() !== '';
    }
}
