<?php

declare(strict_types=1);

namespace App\Support\Export;

/**
 * Export (PDF / Excel / mail) header və footer-lərindəki brend məlumatlarının
 * TƏK mənbəyi.
 *
 * Dəyərlər `config/custom/export.php` → `branding` bölməsindən oxunur.
 * PDF blade-ləri, Excel export sinifləri və mail job-ları - hamısı buradan
 * istifadə etməlidir; başlıq/telefon/sayt heç yerdə hardcode YAZILMIR.
 */
final class ExportBranding
{
    public static function title(): string
    {
        return (string) config('custom.export.branding.title', config('app.name', 'Gopanel'));
    }

    public static function phone(): string
    {
        return (string) config('custom.export.branding.phone', '');
    }

    public static function website(): string
    {
        return (string) config('custom.export.branding.website', '');
    }

    /** `public/` altındakı loqo yolu - PDF-də `<img src>` üçün. */
    public static function logo(): string
    {
        return (string) config('custom.export.branding.logo', '');
    }

    /** "Tel: X  |  sayt" formatında hazır əlaqə sətri (boş sahələr atılır). */
    public static function contactLine(): string
    {
        $parts = array_filter([
            self::phone() !== '' ? 'Tel: ' . self::phone() : '',
            self::website(),
        ]);

        return implode('  |  ', $parts);
    }

    /**
     * Blade-lərə ötürmək üçün hazır massiv.
     *
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return [
            'title'   => self::title(),
            'phone'   => self::phone(),
            'website' => self::website(),
            'logo'    => self::logo(),
            'contact' => self::contactLine(),
        ];
    }
}
