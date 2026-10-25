<?php

namespace App\Helpers\Common;

use Illuminate\Support\Str;

/**
 * Şəxsi məlumatın maskalanması (e-poçt, ad, telefon).
 *
 * Niyə: adminə, loga və ya bildirişə istifadəçinin tam e-poçtu/telefonu
 * göstərilməməli olan yerlər hər layihədə yaranır («k***@g***l.com»). Hər
 * yerdə ayrı `substr` yazılanda bir ekran 3, digəri 5 simvol açır - qayda
 * TƏK yerdə saxlanılır.
 */
class StringHelpers
{
    /**
     * Creates secret data.
     *
     * @param array $data
     * @return array
     */
    public static function obfuscateData(array $data): array
    {
        $obfuscatedData = $data;

        if (isset($obfuscatedData['email'])) {
            $obfuscatedData['email'] = self::obfuscateEmail($obfuscatedData['email']);
        }

        if (isset($obfuscatedData['name'])) {
            $obfuscatedData['name'] = self::obfuscateString($obfuscatedData['name']);
        }

        if (isset($obfuscatedData['surname'])) {
            $obfuscatedData['surname'] = self::obfuscateString($obfuscatedData['surname']);
        }

        if (isset($obfuscatedData['phone'])) {
            $obfuscatedData['phone'] = self::obfuscatePhone($obfuscatedData['phone']);
        }

        return $obfuscatedData;
    }

    /**
     * Creates a hidden email address.
     *
     * @param string $email
     * @return string
     */
    public static function obfuscateEmail(string|null $email): string|null
    {
        if (is_null($email))
            return $email;
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $name = $parts[0];
        $domain = $parts[1];

        $nameHidden = $name[0] . str_repeat('*', max(0, strlen($name) - 3));

        $domainParts = str_split($domain);
        $domainHidden = $domainParts[0] . str_repeat('*', max(0, count($domainParts) - 2)) . end($domainParts);

        return $nameHidden . '@' . $domainHidden;
    }

    /**
     * Creates a secret name.
     *
     * @param string $name
     * @return string
     */
    public static function obfuscateString(string|null $name): string|null
    {
        if (is_null($name))
            return $name;
        return Str::mask($name, '*', 3, strlen($name) - 3);
    }

    /**
     * Creates a private phone number.
     *
     * @param string $phone
     * @return string
     */
    public static function obfuscatePhone(string|null $phone): string|null
    {
        if (is_null($phone))
            return $phone;
        if (strlen($phone) > 7) {
            // İlk 5 + son 2 açıq qalır; ulduz sayı = uzunluq - 7 (mənbə layihədəki
            // nüsxədə `- 5` idi və nömrə 2 simvol uzanırdı).
            $visibleFirstPart = substr($phone, 0, 5);
            $hiddenPart = str_repeat('*', strlen($phone) - 7);
            $visibleLastPart = substr($phone, -2);
            return $visibleFirstPart . $hiddenPart . $visibleLastPart;
        }
        return $phone;
    }
}
