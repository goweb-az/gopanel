<?php

declare(strict_types=1);

namespace App\Jobs\Backup;

use App\Enums\Gopanel\BackupStatus;
use App\Models\Backup\Backup;
use App\Repositories\Gopanel\BackupRepository;
use App\Services\Activity\LogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Backup job-larının ortaq skeleti: vəziyyət qeydiyyatı və xəta idarəsi.
 *
 * Ağır əməliyyatdır (baza dump-ı, GB-larla zip), ona görə növbədə işləyir.
 * Job model obyekti yox, YALNIZ `id` daşıyır (bax: 01-umumi.md § 1) - növbədə
 * gözləyərkən qeyd dəyişə bilər və serializasiya olunmuş köhnə nüsxə ilə
 * işləmək səhv nəticə verir.
 *
 * `$tries = 1` - uğursuz backup avtomatik təkrarlanmır. Səbəb adətən
 * sistemi dəyişməklə həll olunur (disk dolub, mysqldump yoxdur), təkrar
 * cəhd isə eyni xəta ilə serveri yenidən yükləyər.
 */
abstract class BackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $backupId)
    {
        $this->timeout = (int) config('gopanel.backup.job_timeout', 3600);
    }

    public function handle(BackupRepository $repository): void
    {
        $backup = Backup::find($this->backupId);

        if (!$backup) {
            return;
        }

        // İşin HANSI nüsxədə icra olunduğu qeyd olunur.
        // Səbəb: eyni bazaya baxan ikinci quraşdırmanın (məsələn dev) işçisi
        // növbədəki işi «oğurlaya» bilər - onda arxiv başqa qovluğa düşür və
        // panel «Fayl yoxdur» göstərir. Bu sətir olmadan səbəbi tapmaq çətindir.
        $repository->markRunning($backup, [
            'host'      => (string) (gethostname() ?: '—'),
            'base_path' => base_path(),
        ]);

        try {
            $this->run($backup, $repository);

            $repository->markCompleted($backup);
        } catch (Throwable $e) {
            $this->markFailed($backup, $e, $repository);

            throw $e;   // növbənin `failed_jobs` cədvəlinə düşsün
        }
    }

    /** Növbə səviyyəsində uğursuzluq (timeout, işçinin dayandırılması və s.). */
    public function failed(?Throwable $e): void
    {
        $backup = Backup::find($this->backupId);

        if ($backup && $backup->status !== BackupStatus::Failed) {
            $this->markFailed($backup, $e, app(BackupRepository::class));
        }
    }

    /** Konkret backup məntiqi - alt siniflərdə. */
    abstract protected function run(Backup $backup, BackupRepository $repository): void;

    protected function markFailed(Backup $backup, ?Throwable $e, BackupRepository $repository): void
    {
        $repository->markFailed($backup, $e);

        // Səssiz uğursuzluq panelin ən çətin tapılan xətasıdır
        LogService::channel('gopanel')->error('Backup uğursuz oldu', [
            'backup_id' => $backup->id,
            'type'      => $backup->type->value,
            'error'     => $e?->getMessage() ?? 'Naməlum xəta',
        ]);
    }
}
