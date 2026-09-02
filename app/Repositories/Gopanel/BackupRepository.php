<?php

declare(strict_types=1);

namespace App\Repositories\Gopanel;

use App\Enums\Gopanel\BackupStatus;
use App\Models\Backup\Backup;
use App\Repositories\BaseRepository;
use Throwable;

/**
 * Backup qeydinin yazılması.
 *
 * NİYƏ ayrıca sinif: backup-ın vəziyyəti üç ayrı yerdən yazılır - servis
 * (növbəyə salanda), job (başlayanda və bitəndə), job-un `failed()` geri
 * çağırışı. Sütun adları (`started_at`, `finished_at`, `error`) hər üç yerdə
 * təkrarlansaydı, biri unudulanda panel «İşləyir» vəziyyətində ilişib qalardı.
 */
class BackupRepository extends BaseRepository
{
    public function createPending(string $type, ?int $adminId): Backup
    {
        return Backup::create([
            'type'     => $type,
            'status'   => BackupStatus::Pending->value,
            'admin_id' => $adminId,
        ]);
    }

    /**
     * İş başladı: vəziyyət + HANSI nüsxədə icra olunduğu.
     *
     * `ran_on` olmadan «arxiv hazırdır, amma fayl yoxdur» xətasının səbəbini
     * tapmaq mümkün olmur - bax `BackupJob`.
     */
    public function markRunning(Backup $backup, array $ranOn): void
    {
        $backup->update([
            'status'     => BackupStatus::Running->value,
            'started_at' => now(),
            'meta'       => array_merge((array) $backup->meta, ['ran_on' => $ranOn]),
        ]);
    }

    public function markCompleted(Backup $backup): void
    {
        $backup->update([
            'status'      => BackupStatus::Completed->value,
            'finished_at' => now(),
        ]);
    }

    public function markFailed(Backup $backup, ?Throwable $e): void
    {
        $backup->update([
            'status'      => BackupStatus::Failed->value,
            'finished_at' => now(),
            // Uzun stack trace sütunu doldurmasın - səbəb ilk sətirdədir.
            'error'       => mb_substr($e?->getMessage() ?? 'Naməlum xəta', 0, 2000),
        ]);
    }

    /**
     * Arxiv yarandıqdan sonra fayl məlumatları.
     *
     * `meta` üzərinə yazılmır, birləşdirilir: `markRunning()` oraya `ran_on`
     * yazıb və o itməməlidir.
     */
    public function attachArchive(Backup $backup, array $attributes, array $meta = []): void
    {
        $backup->update($attributes + [
            'meta' => array_merge((array) $backup->meta, $meta),
        ]);
    }
}
