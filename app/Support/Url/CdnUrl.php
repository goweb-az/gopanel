<?php

declare(strict_types=1);

namespace App\Support\Url;

use Illuminate\Support\Facades\Storage;

/**
 * Public fayl/asset URL-lərinin TƏK mənbəyi.
 *
 * NİYƏ LAZIMDIR: layihə CDN arxasına keçəndə (`CDN_URL`) modellərdəki şəkil
 * yolları, mail şablonlarındakı loqolar və panel önizləmələri ayrı-ayrı
 * yerlərdə `asset()` / `Storage::url()` ilə qurulursa yarısı köhnə domendə
 * qalır. Buradan keçən hər yol eyni siyasətə tabe olur.
 *
 * MARŞRUTLAMA SİYASƏTİ:
 * ```text
 * boş dəyər            → null
 * http/https ilə başlayır → toxunulmur (kənar URL)
 * assets/...           → CDN (və ya app.url) + yol      → public/ altındakı statik fayl
 * storage/...          → CDN (və ya app.url) + yol      → symlink olunmuş public disk
 * digər nisbi yol      → Storage::disk('public')->url() → yüklənmiş fayl
 * ```
 *
 * DİQQƏT: `Storage::disk('public')->url()` diskin öz konfiqurasiyasına baxır -
 * S3/CDN işlədilirsə `config/filesystems.php` → `disks.public.url` dəyəri də
 * `CDN_URL` ilə uzlaşdırılmalıdır, əks halda iki fərqli domen alınır.
 *
 * Bu sinif YALNIZ public fayllar üçündür. İmzalı/gizli fayllar üçün
 * `Storage::temporaryUrl()` işlədilir - fayl adına baxıb təxmin edilmir.
 */
final class CdnUrl
{
    /** CDN kökü (yoxdursa tətbiqin öz ünvanı), sondakı `/` atılmış. */
    public static function base(): string
    {
        return rtrim((string) (config('app.cdn_url') ?: config('app.url')), '/');
    }

    /** Yol onsuz da tam URL-dirmi? */
    public static function isAbsolute(?string $path): bool
    {
        return !empty($path) && (
            str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
        );
    }

    /** Yüklənmiş fayl (public disk) üçün URL. */
    public static function storage(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (self::isAbsolute($path)) {
            return $path;
        }

        $normalized = self::normalize($path);

        if (str_starts_with($normalized, 'storage/')) {
            return self::base() . '/' . $normalized;
        }

        return Storage::disk('public')->url($normalized);
    }

    /** `public/` altındakı statik fayl (css/js/şəkil) üçün URL. */
    public static function asset(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (self::isAbsolute($path)) {
            return $path;
        }

        return self::base() . '/' . self::normalize($path);
    }

    /**
     * Universal giriş nöqtəsi - yolun növünü özü ayırd edir.
     * Növ əvvəlcədən məlumdursa birbaşa `asset()` / `storage()` çağırılır.
     */
    public static function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (self::isAbsolute($path)) {
            return $path;
        }

        return str_starts_with(self::normalize($path), 'assets/')
            ? self::asset($path)
            : self::storage($path);
    }

    /**
     * Nisbi yolu normallaşdırır: `\` → `/`, baş `/` atılır, `../` bloklanır.
     * `../` qəbul edilsəydi kənardan gələn yol public kökündən yuxarı çıxa bilərdi.
     */
    private static function normalize(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');

        return str_contains($path, '../') ? str_replace('../', '', $path) : $path;
    }
}
