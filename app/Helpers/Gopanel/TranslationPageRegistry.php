<?php

namespace App\Helpers\Gopanel;

/**
 * `config/gopanel/translation_pages.php` kataloqunun tək giriş nöqtəsi.
 *
 * Səhifə adları nə controller-də, nə Blade-də, nə də JS-də hardcode edilmir -
 * hər yer bu registry-dən oxuyur. İkinci registry/helper yaradılmır.
 */
class TranslationPageRegistry
{
    /**
     * Konfiqurasiya boş qalsa belə səhifə seçimi işlək qalsın deyə fallback.
     */
    public const FALLBACK = ['general' => 'Ümumi'];

    /**
     * All configured pages, keyed by platform.
     */
    public function all(): array
    {
        return config('gopanel.translation_pages', []);
    }

    /**
     * Kataloqda elan olunmuş platformalar.
     *
     * @return array<int, string>
     */
    public function platforms(): array
    {
        return array_keys($this->all());
    }

    /**
     * Pages available for a given platform, falling back to the "website"
     * platform's pages, or a bare "general" page when neither is configured.
     */
    public function forPlatform(?string $platform): array
    {
        $pages = $this->all();

        return $pages[$platform] ?? ($pages['website'] ?? self::FALLBACK);
    }

    /**
     * Platformanın səhifə açarları - validasiya (`Rule::in`) üçün.
     *
     * @return array<int, string>
     */
    public function keys(?string $platform): array
    {
        return array_keys($this->forPlatform($platform));
    }

    /**
     * Whether the given page belongs to the given platform's page catalog.
     */
    public function exists(string $platform, string $page): bool
    {
        return array_key_exists($page, $this->forPlatform($platform));
    }

    /**
     * Səhifənin oxunaqlı adı. Kataloqda yoxdursa açarın özü qaytarılır -
     * config-dən silinmiş səhifəyə aid köhnə sətir siyahıda boş görünməsin.
     */
    public function label(?string $platform, ?string $page): string
    {
        $page = $page ?: 'general';

        return $this->forPlatform($platform)[$page] ?? $page;
    }
}
