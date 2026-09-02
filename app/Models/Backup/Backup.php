<?php

declare(strict_types=1);

namespace App\Models\Backup;

use App\Enums\Gopanel\BackupStatus;
use App\Enums\Gopanel\BackupType;
use App\Models\BaseModel;
use App\Models\Gopanel\Admin;
use App\Support\Files\ByteSize;
use App\Traits\System\AddUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Paneldən çıxarılan backup qeydi.
 *
 * Arxivin özü `storage/app/backups/` altındadır (public deyil), ona görə
 * yalnız panel route-u ilə, icazə yoxlamasından keçərək endirilə bilir.
 *
 * Qeyd silinəndə arxiv faylı da silinir - bax `booted()`.
 *
 * @property BackupType   $type
 * @property BackupStatus $status
 */
class Backup extends BaseModel
{
    use AddUuid;

    protected $table = 'backups';

    protected $fillable = [
        'uid',
        'type',
        'mode',
        'status',
        'file_name',
        'path',
        'size',
        'file_count',
        'started_at',
        'finished_at',
        'error',
        'admin_id',
        'meta',
    ];

    protected $casts = [
        'type'        => BackupType::class,
        'status'      => BackupStatus::class,
        'size'        => 'integer',
        'file_count'  => 'integer',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'meta'        => 'array',
    ];

    protected static function booted(): void
    {
        // Qeyd silinəndə arxiv və ona bağlı fayl siyahısı da silinir.
        // Siyahının silinməsi vacibdir: artımlı backup «hansı fayllar artıq
        // arxivlənib» sualına məhz həmin siyahılara baxaraq cavab verir,
        // ona görə silinən arxivin faylları növbəti dəfə yenidən düşməlidir.
        static::deleting(function (self $backup) {
            $backup->deleteArchive();
        });
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /** Arxiv faylı diskdə mövcuddurmu? */
    public function archiveExists(): bool
    {
        return !empty($this->path) && Storage::disk('local')->exists($this->path);
    }

    /** Endirilə bilərmi - hazırdır və faylı yerindədir. */
    public function isDownloadable(): bool
    {
        return $this->status === BackupStatus::Completed && $this->archiveExists();
    }

    /** Arxivin serverdəki tam yolu. */
    public function absolutePath(): string
    {
        return storage_path('app/' . $this->path);
    }

    /** Bu backup-a düşən faylların siyahısı (yalnız `files` tipində). */
    public function manifestPath(): ?string
    {
        return $this->path ? $this->path . '.files.json' : null;
    }

    /** Oxunaqlı ölçü: 5.4 GB, 12 MB... Format `ByteSize`-dədir. */
    public function readableSize(): string
    {
        return ByteSize::humanOrDash((int) $this->size);
    }

    /** Nə qədər çəkib. */
    public function duration(): ?string
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        $seconds = (int) $this->finished_at->diffInSeconds($this->started_at);

        if ($seconds < 60) {
            return $seconds . ' san.';
        }

        return intdiv($seconds, 60) . ' dəq. ' . ($seconds % 60) . ' san.';
    }

    /**
     * Arxivi və fayl siyahısını diskdən silir.
     *
     * Silinə bilmirsə qeyd də silinmir (istisna atılır): əks halda paneldə
     * sətir yox olar, GB-larla fayl isə diskdə sahibsiz qalar və heç kim
     * onun nə olduğunu bilməz.
     *
     * Ən çox rast gəlinən səbəb icazədir: faylı silmək üçün onun özündə
     * deyil, QOVLUQDA yazma hüququ lazımdır
     * (bax: `gopanel.backup.directory_permission`, docs/BACKUP_PERMISSIONS.md).
     */
    private function deleteArchive(): void
    {
        $disk = Storage::disk('local');

        foreach ([$this->path, $this->manifestPath()] as $path) {
            if (empty($path) || !$disk->exists($path)) {
                continue;
            }

            if (!$disk->delete($path)) {
                throw new RuntimeException(
                    'Arxiv faylı silinə bilmədi: ' . storage_path('app/' . $path)
                    . ' - qovluğa yazma icazəsi yoxdur. Qeyd də silinmədi ki,'
                    . ' fayl diskdə sahibsiz qalmasın.'
                );
            }
        }
    }
}
