<?php

namespace App\Traits\Ui;

use Carbon\Carbon;

trait FormatsDate
{
    public function formatDate(string $key, string $format = 'd F Y'): ?string
    {
        if (!isset($this->$key) || empty($this->$key)) {
            return null;
        }

        Carbon::setLocale('az');
        return Carbon::parse($this->$key)->translatedFormat($format);
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
