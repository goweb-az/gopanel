<?php

declare(strict_types=1);

namespace App\Support\Http;

/**
 * Ziyarətçinin mobil əməliyyat sistemini `User-Agent`-dən təyin edir.
 *
 * Yalnız «hansı mağazanı önə çıxaraq?» sualına cavab verir (App Store /
 * Google Play / AppGallery düyməsi, `/endir` yönləndirməsi) - dəqiq cihaz
 * tanıması məqsəd deyil. Tanınmayan halda `null` qaytarılır və çağıran tərəf
 * bütün mağazaları göstərir.
 *
 * Bot/önizləmə robotunu ayırmaq üçün `PreviewBotDetector` işlədilir -
 * robotu mağazaya yönləndirmək paylaşılan linkin önizləmə kartını pozur.
 */
final class MobilePlatform
{
    public const IOS     = 'ios';
    public const ANDROID = 'android';
    public const HUAWEI  = 'huawei';

    /** @return string|null `ios`, `android`, `huawei` və ya `null` */
    public static function detect(?string $userAgent): ?string
    {
        $ua = trim((string) $userAgent);

        if ($ua === '') {
            return null;
        }

        // Huawei Android üzərində işləyir, ona görə Android-dən ƏVVƏL
        // yoxlanır - əks halda AppGallery heç vaxt seçilməzdi.
        if (self::contains($ua, ['HarmonyOS', 'HuaweiBrowser', 'HMSCore', 'HUAWEI', 'Honor'])) {
            return self::HUAWEI;
        }

        if (self::contains($ua, ['iPhone', 'iPad', 'iPod', 'iOS', 'CriOS', 'FxiOS'])) {
            return self::IOS;
        }

        if (self::contains($ua, ['Android'])) {
            return self::ANDROID;
        }

        return null;
    }

    /** @param  list<string>  $needles */
    private static function contains(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (stripos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
