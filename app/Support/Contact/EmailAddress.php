<?php

declare(strict_types=1);

namespace App\Support\Contact;

/**
 * E-poçt ünvanlarının normallaşdırılması.
 *
 * İstifadəçi ünvanı müxtəlif registrlərdə və artıq boşluqlarla yaza bilər
 * (`Test@Mail.AZ`, ` test@mail.az `). Baza həmişə tək formada saxlayır:
 * kiçik hərflərlə və kənar boşluqsuz. Beləliklə eyni ünvanla iki hesab yaranmır.
 */
final class EmailAddress
{
    /**
     * @return string|null  normallaşdırılmış ünvan, düzgün deyilsə `null`
     */
    public static function normalize(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = mb_strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /** Validasiya qaydası üçün: ünvan düzgün formadadırmı. */
    public static function isValid(?string $email): bool
    {
        return self::normalize($email) !== null;
    }

    /**
     * Maskalanmış ünvan - bildirişlərdə və loglarda tam ünvan göstərilmir.
     */
    public static function mask(?string $email): ?string
    {
        $normalized = self::normalize($email);

        if ($normalized === null) {
            return null;
        }

        [$local, $domain] = explode('@', $normalized, 2);

        // Local hissənin yalnız ilk hərfi açıq qalır, qalanı gizlədilir.
        return mb_substr($local, 0, 1) . '***@' . $domain;
    }
}
