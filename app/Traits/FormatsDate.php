<?php

namespace App\Traits;

use Carbon\Carbon;

trait FormatsDate
{
    /**
     * Verilmiş tarix sahəsini formatlayır.
     *
     * @param string $key     Modelin tarix sahəsinin adı (məs. created_at).
     * @param string $format  Tarix formatı (məs. 'd F Y' → 25 iyul 2025).
     * @return string|null
     */
    public function formatDate(string $key, string $format = 'd F Y'): ?string
    {
        if (!isset($this->$key) || empty($this->$key)) {
            return null;
        }

        Carbon::setLocale('az');
        return Carbon::parse($this->$key)->translatedFormat($format);
    }

    /**
     * @return string|null
     * created_at sahəsini formatlı şəkildə qaytarır (məs: 25 iyul 2025).
     */
    public function getCreatedAtFormattedAttribute(): ?string
    {
        return $this->formatDate('created_at');
    }

    /**
     * @return string|null
     * updated_at sahəsini formatlı şəkildə qaytarır (məs: 25 iyul 2025).
     */
    public function getUpdatedAtFormattedAttribute(): ?string
    {
        return $this->formatDate('updated_at');
    }

    /**
     * @return string|null
     * startdate sahəsini formatlı şəkildə qaytarır (məs: 25 iyul 2025).
     */
    public function getStartdateFormattedAttribute(): ?string
    {
        return $this->formatDate('startdate', 'd F');
    }

    /**
     * @return string|null
     * enddate sahəsini formatlı şəkildə qaytarır (məs: 25 iyul 2025).
     */
    public function getEnddateFormattedAttribute(): ?string
    {
        return $this->formatDate('enddate', 'd F');
    }

    /**
     * @return string|null
     * started_at sahəsini qısa formatda qaytarır (məs: 25 iyul).
     */
    public function getStartedAtFormattedAttribute(): ?string
    {
        return $this->formatDate('started_at');
    }

    /**
     * @return string|null
     * finished_at sahəsini qısa formatda qaytarır (məs: 25 iyul).
     */
    public function getFinishedAtFormattedAttribute(): ?string
    {
        return $this->formatDate('finished_at');
    }

    /**
     * @return string|null
     * archived_at sahəsini formatlı şəkildə qaytarır (məs: 25 iyul 2025).
     */
    public function getArchivedAtFormattedAttribute(): ?string
    {
        return $this->formatDate('archived_at');
    }
}
