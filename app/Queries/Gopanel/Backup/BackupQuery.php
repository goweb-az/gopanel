<?php

declare(strict_types=1);

namespace App\Queries\Gopanel\Backup;

use App\Enums\Gopanel\BackupStatus;
use App\Enums\Gopanel\BackupType;
use App\Models\Backup\Backup;
use Illuminate\Database\Eloquent\Collection;

/**
 * Backup bölməsinin bütün oxuma sorğuları.
 *
 * NİYƏ Query sinifi: servis yalnız qərar verir (başlamaq olar/olmaz), sorğu
 * isə buradadır - eyni «hazır olan sonuncu backup» sualı həm səhifə
 * başlığında, həm artımlı arxiv məntiqində lazımdır və iki yerdə fərqli
 * yazılsaydı, panel bir tarixi, arxiv isə başqasını əsas götürərdi.
 */
class BackupQuery
{
    /** Bu tipdə növbədə və ya işləyən backup varmı. */
    public function hasRunning(?BackupType $type = null): bool
    {
        return Backup::query()
            ->when($type, fn ($q) => $q->where('type', $type->value))
            ->whereIn('status', [BackupStatus::Pending->value, BackupStatus::Running->value])
            ->exists();
    }

    public function lastCompleted(BackupType $type): ?Backup
    {
        return Backup::query()
            ->where('type', $type->value)
            ->where('status', BackupStatus::Completed->value)
            ->latest('finished_at')
            ->first();
    }

    /** Uğursuz arxivlərin sayı - «Sistem vəziyyəti» səhifəsi göstərir. */
    public function failedCount(): int
    {
        return (int) Backup::query()
            ->where('status', BackupStatus::Failed->value)
            ->count();
    }

    /** Diskdə saxlanılan bütün hazır arxivlərin cəmi. */
    public function completedSize(): int
    {
        return (int) Backup::query()
            ->where('status', BackupStatus::Completed->value)
            ->sum('size');
    }

    /**
     * Fayl arxivlərinin manifest yolları.
     *
     * Yalnız `id` və `path` seçilir - manifestlər diskdən oxunacaq, modelin
     * qalan sütunları lazım deyil.
     */
    public function completedFileBackups(): Collection
    {
        return Backup::query()
            ->where('type', BackupType::Files->value)
            ->where('status', BackupStatus::Completed->value)
            ->whereNotNull('path')
            ->get(['id', 'path']);
    }
}
