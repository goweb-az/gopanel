<?php

namespace App\Helpers\Gopanel;

class TranslationPageRegistry
{
    /**
     * All configured pages, keyed by platform.
     */
    public function all(): array
    {
        return config('gopanel.translation_pages', []);
    }

    /**
     * Pages available for a given platform, falling back to the "website"
     * platform's pages, or a bare "general" page when neither is configured.
     */
    public function forPlatform(?string $platform): array
    {
        $pages = $this->all();

        return $pages[$platform] ?? ($pages['website'] ?? ['general' => 'Ümumi']);
    }

    /**
     * Whether the given page belongs to the given platform's page catalog.
     */
    public function exists(string $platform, string $page): bool
    {
        return array_key_exists($page, $this->forPlatform($platform));
    }
}
