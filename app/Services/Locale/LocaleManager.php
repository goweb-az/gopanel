<?php

namespace App\Services\Locale;

use App\Models\Geography\Language;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class LocaleManager
{
    private ?bool $tableExists = null;

    public function current(): string
    {
        return app()->getLocale();
    }

    public function all(): Collection
    {
        if (! $this->hasTable()) {
            return collect();
        }

        return Language::getCachedAll();
    }

    public function default(): ?Language
    {
        if (! $this->hasTable()) {
            return null;
        }

        return Language::getDefault();
    }

    public function defaultCode(string $fallback = 'az'): string
    {
        if (! $this->hasTable()) {
            return $fallback;
        }

        return Language::getDefaultCode($fallback);
    }

    public function clearCache(): void
    {
        Language::clearLanguageCache();
    }

    private function hasTable(): bool
    {
        return $this->tableExists ??= Schema::hasTable('languages');
    }
}
