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
     * Loqonun MÜTLƏQ fayl yolu (tapılmazsa `null`) - PDF üçün.
     *
     * NİYƏ URL yox: Dompdf `<img src>`-də URL görəndə şəbəkə sorğusu atır -
     * bu PDF qurulmasını yavaşladır, növbə işçisində isə CDN/sayta çıxış
     * olmaya bilər. `logo()` nisbi yolu verir (geriyə uyğunluq üçün olduğu
     * kimi saxlanılıb), bu metod onu `public/` altında axtarır.
     *
     * Mütləq yol verilibsə (`/var/www/.../logo.png`) olduğu kimi yoxlanılır.
     */
    public static function logoPath(): ?string
    {
        $logo = trim(self::logo());

        if ($logo === '') {
            return null;
        }

        if (is_file($logo)) {
            return $logo;
        }

        $path = public_path(ltrim($logo, '/'));

        return is_file($path) ? $path : null;
    }

    /**
     * Brend rəngi (HEX, `#` olmadan) - `config/custom/export.php` → `colors`.
     *
     * Excel `RRGGBB` gözləyir, CSS isə `#RRGGBB`; ona görə dəyər `#`-siz
     * saxlanılır və PDF-də `cssColor()` ilə verilir.
     */
    public static function color(string $key, string $default = '000000'): string
    {
        return ltrim((string) config('custom.export.colors.' . $key, $default), '#');
    }

    /** `#RRGGBB` - PDF/mail şablonları üçün. */
    public static function cssColor(string $key, string $default = '000000'): string
    {
        return '#' . self::color($key, $default);
    }

    /**
     * Blade-lərə ötürmək üçün hazır massiv.
     *
     * `logo_path` sonradan əlavə olunub (PDF üçün); köhnə açarlar dəyişməyib.
     *
     * @return array<string, string|null>
     */
    public static function toArray(): array
    {
        return [
            'title'     => self::title(),
            'phone'     => self::phone(),
            'website'   => self::website(),
            'logo'      => self::logo(),
            'contact'   => self::contactLine(),
            'logo_path' => self::logoPath(),
        ];
    }
}
