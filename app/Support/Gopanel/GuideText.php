<?php

declare(strict_types=1);

namespace App\Support\Gopanel;

/**
 * Təlimat mətnindəki sadə işarələri HTML-ə çevirir.
 *
 * JSON faylına xam HTML yazmaq iki problem yaradır: fayl oxunmaz olur və
 * mətnə düşən istənilən teq səhifəni poza bilər. Ona görə yalnız iki işarə
 * tanınır, qalan hər şey qaçırılır:
 *
 *     **qalın**   → <strong>qalın</strong>
 *     `kod`       → <code>kod</code>
 *
 * Ardıcıllıq vacibdir: əvvəlcə `e()` ilə qaçırılır, sonra işarələr açılır --
 * beləliklə mətndəki `<script>` heç vaxt icra olunmur.
 */
final class GuideText
{
    public static function inline(?string $text): string
    {
        $escaped = e((string) $text);

        // `kod` - əvvəl işlənir ki, kod bloku daxilindəki ** işarəsi
        // qalın kimi açılmasın.
        $escaped = (string) preg_replace('/`([^`]+)`/u', '<code>$1</code>', $escaped);

        return (string) preg_replace('/\*\*([^*]+)\*\*/u', '<strong>$1</strong>', $escaped);
    }
}
