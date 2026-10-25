<?php

namespace App\Traits\Ui;

use App\Support\Date\LocalizedDateFormatter;

/**
 * Model sahəsinin dilə görə mətni. Nüvə: `App\Support\Date\LocalizedDateFormatter`.
 *
 * Dil - sorğunun app locale-i (panel-də `az`, saytda `language` middleware-i,
 * API-də `Accept-Language`). Əvvəl hər çağırış `Carbon::setLocale('az')` edirdi:
 * həm ingilis səhifəsində tarix azərbaycanca çıxırdı, həm də QLOBAL locale
 * dəyişdiyi üçün növbə worker-ində sonrakı işlərin dili «sızırdı».
 * Formatter yalnız nüsxə səviyyəsində `->locale()` işlədir.
 */
trait FormatsDate
{
    public function formatDate(string $key, string $format = LocalizedDateFormatter::DATE): ?string
    {
        if (!isset($this->$key) || empty($this->$key)) {
            return null;
        }

        return LocalizedDateFormatter::format($this->$key, $format);
    }

    /**
     * Tarix + saat: «27 sentyabr 2026, 01:03».
     *
     * Ayrıca metod ona görə var ki, panel siyahılarında hər yerdə format sətri
     * (`'d F Y, H:i'`) təkrar yazılmasın - format bir yerdə dəyişir.
     */
    public function formatDateTime(string $key, string $format = LocalizedDateFormatter::DATE_TIME): ?string
    {
        return $this->formatDate($key, $format);
    }

    public function getCreatedAtFormattedAttribute(): ?string
    {
        return $this->formatDate('created_at');
    }

    public function getUpdatedAtFormattedAttribute(): ?string
    {
        return $this->formatDate('updated_at');
    }

    public function getStartdateFormattedAttribute(): ?string
    {
        return $this->formatDate('startdate', 'd F');
    }

    public function getEnddateFormattedAttribute(): ?string
    {
        return $this->formatDate('enddate', 'd F');
    }

    public function getStartedAtFormattedAttribute(): ?string
    {
        return $this->formatDate('started_at');
    }

    public function getFinishedAtFormattedAttribute(): ?string
    {
        return $this->formatDate('finished_at');
    }

    public function getArchivedAtFormattedAttribute(): ?string
    {
        return $this->formatDate('archived_at');
    }
}
