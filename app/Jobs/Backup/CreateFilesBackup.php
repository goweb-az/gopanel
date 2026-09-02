<?php

declare(strict_types=1);

namespace App\Jobs\Backup;

use App\Enums\Gopanel\BackupType;
use App\Models\Backup\Backup;
use App\Repositories\Gopanel\BackupRepository;
use App\Services\Gopanel\Backup\BackupService;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Paneldən yüklənən faylların (`public/site`) ARTIMLI arxivlənməsi.
 *
 * Niyə artımlı:
 *   Qovluq GB-larla ola bilər və demək olar hamısı jpg/png/mp4 - hər dəfə tam
 *   zip çıxarmaq diski doldurar və hər arxiv onlarla dəqiqə çəkər. Panel
 *   yükləmələrində fayl adı `uniqid()` ilə unikaldır, yəni mövcud fayl
 *   heç vaxt üzərinə yazılmır. Ona görə hər arxivə YALNIZ əvvəlkilərdə
 *   olmayan fayllar düşür və arxivlərin cəmi = bütün fayllar.
 *
 * Bərpa: bütün `files` arxivləri (köhnədən yeniyə) eyni qovluğa açılır.
 */
class CreateFilesBackup extends BackupJob
{
    protected function run(Backup $backup, BackupRepository $repository): void
    {
        $service = app(BackupService::class);
        $disk    = Storage::disk('local');

        $source = (string) config('gopanel.backup.files_source');

        if (!is_dir($source)) {
            throw new RuntimeException('Mənbə qovluğu tapılmadı: ' . $source);
        }

        // `Storage::makeDirectory()` işlədilmir - qovluq 0700 yaranır və veb
        // server arxivi görmür (bax: BackupService::ensureFolder).
        $folder = $service->ensureFolder(BackupType::Files);

        $isFirst = $service->isFirstFilesBackup();

        [$newFiles, $newSize] = $service->newFiles();

        // Servis bunu başlamazdan əvvəl də yoxlayır; burada yalnız job
        // növbədə gözləyərkən vəziyyət dəyişibsə işə düşür.
        if ($newFiles === []) {
            throw new RuntimeException(
                'Yeni fayl yoxdur - sonuncu backup-dan bəri heç nə yüklənməyib.'
            );
        }

        $this->guardSpace($service, $newSize);

        $mode     = $isFirst ? 'full' : 'incremental';
        $fileName = 'files-' . now()->format('Y-m-d-His') . '-' . $mode . '.zip';
        $relative = $folder . '/' . $fileName;
        $target   = storage_path('app/' . $relative);

        $this->writeArchive($source, $newFiles, $target);

        // Bu arxivə hansı faylların düşdüyü - növbəti artımlı backup buna baxır.
        // Arxivin yanında saxlanılır ki, qeyd silinəndə siyahı da getsin.
        $disk->put($relative . '.files.json', json_encode(
            $newFiles,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));

        // Siyahını PANEL də oxuyur («yeni fayl varmı» yoxlaması). Oxunmasa
        // sistem «heç nə arxivlənməyib» qərarına gəlir və növbəti backup
        // bütün qovluğu yenidən arxivləyir.
        $service->protectFile($target);
        $service->protectFile(storage_path('app/' . $relative . '.files.json'));

        $repository->attachArchive($backup, [
            'mode'       => $mode,
            'file_name'  => $fileName,
            'path'       => $relative,
            'size'       => (int) (@filesize($target) ?: 0),
            'file_count' => count($newFiles),
        ], [
            'source'       => $source,
            'source_bytes' => $newSize,
        ]);
    }

    /** Arxiv üçün yer varmı - sıxılma qazancı olmadığı üçün ~1.1× götürülür. */
    private function guardSpace(BackupService $service, int $needed): void
    {
        $free     = $service->freeSpace();
        $required = (int) ($needed * 1.1) + (int) config('gopanel.backup.min_free_space');

        if ($free > 0 && $free < $required) {
            throw new RuntimeException(
                'Diskdə yer çatmır. Lazımdır: ' . $service->readableBytes($required)
                . ', boşdur: ' . $service->readableBytes($free)
                . '. Köhnə backup-ları silin.'
            );
        }
    }

    /**
     * @param  list<string>  $files
     */
    private function writeArchive(string $source, array $files, string $target): void
    {
        $zip = new ZipArchive();

        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Arxiv yaradıla bilmədi: ' . $target);
        }

        $base = rtrim($source, '/\\');

        foreach ($files as $relative) {
            $full = $base . '/' . $relative;

            if (!is_file($full)) {
                continue;   // job işləyərkən silinibsə atlanır
            }

            // Şəkil və videolar onsuz da sıxılıb - yenidən sıxmaq yalnız
            // vaxt aparır, ona görə saxlama üsulu STORE seçilir.
            $zip->addFile($full, 'site/' . $relative);
            $zip->setCompressionName('site/' . $relative, ZipArchive::CM_STORE);
        }

        if (!$zip->close()) {
            throw new RuntimeException('Arxiv bağlanarkən xəta baş verdi.');
        }

        if (!is_file($target) || filesize($target) === 0) {
            throw new RuntimeException('Arxiv boş çıxdı.');
        }
    }
}
