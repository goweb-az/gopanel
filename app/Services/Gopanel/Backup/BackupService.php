<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Backup;

use App\Enums\Gopanel\BackupType;
use App\Jobs\Backup\CreateDatabaseBackup;
use App\Jobs\Backup\CreateFilesBackup;
use App\Models\Backup\Backup;
use App\Queries\Gopanel\Backup\BackupQuery;
use App\Repositories\Gopanel\BackupRepository;
use App\Support\Files\ByteSize;
use Generator;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SplFileInfo;

/**
 * Backup əməliyyatlarının iş məntiqi.
 *
 * Controller yalnız çağırır - yoxlamalar, qeydin yaradılması və növbəyə
 * göndərmə burada olur.
 */
class BackupService
{
    public function __construct(
        private readonly BackupQuery $query,
        private readonly BackupRepository $repository,
    ) {
    }

    /**
     * Yeni backup başladır (növbəyə salır).
     *
     * @throws RuntimeException istifadəçiyə göstəriləcək səbəblə
     */
    public function start(BackupType $type, ?int $adminId = null): Backup
    {
        $this->guardNotRunning($type);
        $this->guardFreeSpace();

        // Artımlı fayl backup-ında yeni fayl yoxdursa qeyd ümumiyyətlə
        // yaradılmır - bu, xəta deyil, ona görə siyahıda «Xəta» sətri
        // görünməməlidir.
        if ($type === BackupType::Files && $this->newFiles()[0] === []) {
            throw new RuntimeException(
                'Yeni fayl yoxdur - sonuncu backup-dan bəri heç nə yüklənməyib.'
            );
        }

        $backup = $this->repository->createPending($type->value, $adminId);

        match ($type) {
            BackupType::Database => CreateDatabaseBackup::dispatch($backup->id),
            BackupType::Files    => CreateFilesBackup::dispatch($backup->id),
        };

        return $backup;
    }

    /**
     * Artımlı backup üçün: hansı fayllar ARTIQ arxivlənib.
     *
     * Vahid siyahı saxlanılmır - hər uğurlu backup-ın öz siyahısı arxivin
     * yanındadır və burada birləşdirilir. Beləliklə paneldən bir backup
     * silinəndə onun faylları avtomatik «arxivlənməmiş» sayılır və növbəti
     * dəfə yenidən düşür; ayrıca təmizləmə məntiqinə ehtiyac qalmır.
     *
     * @return array<string, true> açar = nisbi yol (sürətli axtarış üçün)
     */
    public function archivedFiles(): array
    {
        $disk  = Storage::disk('local');
        $known = [];

        foreach ($this->query->completedFileBackups() as $backup) {
            $manifest = $backup->manifestPath();

            if (!$manifest || !$disk->exists($manifest)) {
                continue;
            }

            $paths = json_decode((string) $disk->get($manifest), true);

            if (!is_array($paths)) {
                continue;
            }

            foreach ($paths as $path) {
                $known[$path] = true;
            }
        }

        return $known;
    }

    /**
     * Hələ arxivlənməmiş fayllar.
     *
     * Həm servis (başlamazdan əvvəlki yoxlama), həm də job (arxivin özü)
     * eyni siyahını işlədir - məntiq tək yerdədir.
     *
     * @return array{0: list<string>, 1: int}  [nisbi yollar, ümumi ölçü]
     */
    public function newFiles(): array
    {
        $source = (string) config('gopanel.backup.files_source');

        if (!is_dir($source)) {
            throw new RuntimeException('Mənbə qovluğu tapılmadı: ' . $source);
        }

        $archived = $this->archivedFiles();
        $base     = rtrim($source, '/\\');
        $files    = [];
        $size     = 0;

        foreach ($this->iterateSource($base) as $file) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));

            if (isset($archived[$relative])) {
                continue;
            }

            $files[] = $relative;
            $size   += $file->getSize();
        }

        return [$files, $size];
    }

    /** İlk fayl backup-ıdırmı - arxivlənmiş fayl yoxdursa bəli. */
    public function isFirstFilesBackup(): bool
    {
        return $this->archivedFiles() === [];
    }

    /**
     * Backup bölməsinin yuxarısındakı qısa statistika.
     *
     * Dəyərlər burada oxunaqlı formaya salınır - blade-də hesablama olmur
     * (bax: 01-umumi.md § 3).
     */
    public function summary(): array
    {
        $databaseLast = $this->query->lastCompleted(BackupType::Database);
        $filesLast    = $this->query->lastCompleted(BackupType::Files);

        return [
            'in_progress'    => $this->query->hasRunning(),

            'total_size'     => ByteSize::human($this->query->completedSize()),
            'free_space'     => ByteSize::human($this->freeSpace()),
            'source_size'    => ByteSize::human($this->sourceSize()),

            'database_date'  => $databaseLast?->finished_at?->format('d.m.Y H:i'),
            'database_size'  => $databaseLast?->readableSize(),
            'files_date'     => $filesLast?->finished_at?->format('d.m.Y H:i'),
            'files_size'     => $filesLast?->readableSize(),
            'files_has_base' => $filesLast !== null,
        ];
    }

    /** Arxivlərin yerləşdiyi qovluğun `storage/app` daxilindəki yolu. */
    public function folder(BackupType $type): string
    {
        return trim((string) config('gopanel.backup.root', 'backups'), '/') . '/' . $type->folder();
    }

    /**
     * Arxiv qovluğunu yaradır və icazələrini düzəldir.
     *
     * `Storage::makeDirectory()` işlədilmir: `local` diskin görünürlüyü
     * «private»dır, qovluq 0700 yaranır və onu YALNIZ işçinin istifadəçisi
     * oxuya bilir. Panel isə veb server istifadəçisi altında işləyir -
     * nəticədə hazır arxiv siyahıda «Fayl yoxdur» görünür və endirilmir.
     *
     * Detallar: docs/BACKUP_PERMISSIONS.md
     *
     * @return string `storage/app` daxilində nisbi yol
     */
    public function ensureFolder(BackupType $type): string
    {
        $relative = $this->folder($type);
        $mode     = (int) config('gopanel.backup.directory_permission', 02770);
        $root     = trim((string) config('gopanel.backup.root', 'backups'), '/');

        // Həm kök (`backups`), həm də tip qovluğu düzəldilir - kök əvvəlki
        // versiyada 0700 yaradılmış ola bilər.
        foreach ([$root, $relative] as $folder) {
            $absolute = storage_path('app/' . $folder);

            if (!is_dir($absolute)) {
                @mkdir($absolute, $mode, true);
            }

            @chmod($absolute, $mode);
            $this->inheritGroup($absolute);
        }

        return $relative;
    }

    /** Yazılmış arxivə/siyahıya oxunaqlı icazə verir (bax: `ensureFolder`). */
    public function protectFile(string $absolutePath): void
    {
        if (!is_file($absolutePath)) {
            return;
        }

        @chmod($absolutePath, (int) config('gopanel.backup.file_permission', 0640));
        $this->inheritGroup($absolutePath);
    }

    /**
     * Qovluğun/faylın qrupunu `storage/app` ilə eyniləşdirir.
     *
     * Setgid biti adətən bunu özü edir, amma qovluq daha əvvəl səhv qrupla
     * yaradılıbsa (məsələn əl ilə `chmod 770` setgid-i silibsə) düzəlmir.
     * Bu halda qrup açıq şəkildə bərpa olunur.
     *
     * `chgrp` yalnız işçi həmin qrupun üzvüdürsə alınır - alınmasa səssiz
     * keçilir, çünki bu, backup-ın özünü dayandırmamalıdır.
     */
    private function inheritGroup(string $path): void
    {
        $group = @filegroup(storage_path('app'));

        if ($group !== false && @filegroup($path) !== $group) {
            @chgrp($path, $group);
        }
    }

    public function freeSpace(): int
    {
        $bytes = @disk_free_space(storage_path());

        return $bytes === false ? 0 : (int) $bytes;
    }

    /** Oxunaqlı bayt - job-lar xəta mətnində işlədir. */
    public function readableBytes(int $bytes): string
    {
        return ByteSize::human($bytes);
    }

    /**
     * Mənbə qovluğunu rekursiv gəzir (gizli fayllar atlanır).
     *
     * `Generator` qaytarır: 5+ GB-lıq qovluqda bütün `SplFileInfo` obyektlərini
     * massivə yığmaq yaddaşı doldurur.
     *
     * @return Generator<SplFileInfo>
     */
    public function iterateSource(string $source): Generator
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                yield $file;
            }
        }
    }

    /** Mənbə qovluğunun ölçüsü - səhifədə «nə qədər məlumat var» göstəricisi. */
    private function sourceSize(): int
    {
        $source = (string) config('gopanel.backup.files_source');

        if (!is_dir($source)) {
            return 0;
        }

        $total = 0;

        foreach ($this->iterateSource($source) as $file) {
            $total += $file->getSize();
        }

        return $total;
    }

    /**
     * Eyni tipdə işləyən backup varsa yenisi başlamır.
     *
     * Səbəb: iki paralel `mysqldump` və ya iki zip prosesi serveri yükləyir,
     * artımlı fayl backup-ında isə eyni faylların iki arxivə düşməsinə
     * gətirir.
     */
    private function guardNotRunning(BackupType $type): void
    {
        if ($this->query->hasRunning($type)) {
            throw new RuntimeException(
                $type->title() . ' backup-ı artıq növbədədir. Bitməsini gözləyin.'
            );
        }
    }

    private function guardFreeSpace(): void
    {
        $min  = (int) config('gopanel.backup.min_free_space');
        $free = $this->freeSpace();

        if ($free > 0 && $free < $min) {
            throw new RuntimeException(
                'Diskdə yer azdır (' . ByteSize::human($free) . ').'
                . ' Ən azı ' . ByteSize::human($min) . ' boş yer lazımdır.'
                . ' Köhnə backup-ları silin.'
            );
        }
    }
}
