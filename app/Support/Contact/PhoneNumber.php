<?php

declare(strict_types=1);

namespace App\Support\Contact;

/**
 * Azərbaycan mobil nömrəsinin baza formatına gətirilməsi.
 *
 * Baza nömrəni TƏK formada saxlamalıdır: `994` + operator (2 rəqəm) + 7 rəqəm.
 * İstifadəçi isə nömrəni çox cür yazır: `+994552725195`, `00994 55 272 51 95`,
 * `0552725195`, `(055) 272-51-95`, `552725195`. Normallaşdırılmasa eyni nömrə
 * ilə iki hesab yaranır, SMS göndərişi və axtarış tutmur.
 *
 * Ön tərəfdəki maska tək müdafiə deyil: Excel idxalı, API və maskasız açılan
 * forma da eyni nömrəni göndərə bilər. Ona görə normallaşdırma serverdə
 * validasiyadan ƏVVƏL tətbiq olunur (FormRequest → `prepareForValidation()`).
 *
 * Tanınmayan formatda dəyər OLDUĞU KİMİ qaytarılır - validasiya qaydası onu
 * onsuz da rədd edir və istifadəçi öz yazdığını görür.
 */
final class PhoneNumber
{
    /** Mobil operator kodları - `phone_regex` ilə eyni siyahı. */
    private const OPERATORS = ['50', '51', '10', '55', '60', '99', '70', '77'];

    /** Operatordan sonrakı rəqəm sayı. */
    private const SUBSCRIBER_LENGTH = 7;

    /**
     * Azərbaycan mobil nömrəsini `994XXXXXXXXX` formatına gətirir.
     *
     * @param  string|null  $raw  istənilən formatda nömrə
     * @return string  normallaşmış nömrə; alınmasa - təmizlənmiş orijinal
     */
    public static function normalize(?string $raw): string
    {
        $original = trim((string) $raw);

        if ($original === '') {
            return '';
        }

        $digits = preg_replace('/\D/', '', $original) ?? '';

        // Ölkə kodu: `00994...` / `994...` / `+994...` (plus onsuz da silinib)
        if (str_starts_with($digits, '00994')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '994')) {
            $digits = substr($digits, 3);
        }

        // Ölkə daxili `0` prefiksi: `0552725195`
        if (strlen($digits) === self::SUBSCRIBER_LENGTH + 3 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) !== self::SUBSCRIBER_LENGTH + 2) {
            return $original;
        }

        if (!in_array(substr($digits, 0, 2), self::OPERATORS, true)) {
            return $original;
        }

        return '994' . $digits;
    }

    /**
     * Massivdəki nömrə sahələrini normallaşdırır (nöqtəli yol dəstəklənir).
     *
     * Nümunə: `PhoneNumber::normalizeIn($data, ['owner.phone', 'personals.*.phone'])`
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>    $paths
     * @return array<string, mixed>
     */
    public static function normalizeIn(array $data, array $paths): array
    {
        foreach ($paths as $path) {
            $data = self::applyPath($data, explode('.', $path));
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>    $segments
     * @return array<string, mixed>
     */
    private static function applyPath(array $data, array $segments): array
    {
        $segment = array_shift($segments);

        if ($segment === null) {
            return $data;
        }

        // `*` - massivin bütün sətirləri
        if ($segment === '*') {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = self::applyPath($value, $segments);
                }
            }

            return $data;
        }

        if (!array_key_exists($segment, $data)) {
            return $data;
        }

        // Yolun sonu - dəyəri normallaşdır
        if ($segments === []) {
            if (is_string($data[$segment]) || is_numeric($data[$segment])) {
                $data[$segment] = self::normalize((string) $data[$segment]);
            }

            return $data;
        }

        if (is_array($data[$segment])) {
            $data[$segment] = self::applyPath($data[$segment], $segments);
        }

        return $data;
    }
}
