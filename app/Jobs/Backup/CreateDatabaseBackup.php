<?php

declare(strict_types=1);

namespace App\Jobs\Backup;

use App\Enums\Gopanel\BackupType;
use App\Models\Backup\Backup;
use App\Repositories\Gopanel\BackupRepository;
use App\Services\Gopanel\Backup\BackupService;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Bazanın `mysqldump` ilə arxivlənməsi.
 *
 * Parol nə koda, nə də əmr sətrinə yazılır: müvəqqəti `defaults-extra-file`
 * yaradılır (0600) və iş bitəndə silinir. Əmr sətrində olsaydı serverdə
 * `ps aux` işlədən hər kəs parolu görərdi.
 */
class CreateDatabaseBackup extends BackupJob
{
    protected function run(Backup $backup, BackupRepository $repository): void
    {
        $service = app(BackupService::class);

        // `Storage::makeDirectory()` işlədilmir - qovluq 0700 yaranır və veb
        // server arxivi görmür (bax: BackupService::ensureFolder).
        $folder = $service->ensureFolder(BackupType::Database);

        $connection = config('database.default');
        $config     = config("database.connections.{$connection}");

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('Backup yalnız MySQL bağlantısı üçün dəstəklənir.');
        }

        $database = (string) $config['database'];
        $fileName = 'db-' . $database . '-' . now()->format('Y-m-d-His') . '.sql.gz';

        $relative = $folder . '/' . $fileName;
        $target   = storage_path('app/' . $relative);
        $rawFile  = $target . '.tmp';

        $cnfFile = $this->writeCredentialsFile($config);

        try {
            $this->dump($cnfFile, $database, $rawFile);
            $this->compress($rawFile, $target);
        } finally {
            @unlink($cnfFile);
            @unlink($rawFile);
        }

        $service->protectFile($target);

        $repository->attachArchive($backup, [
            'file_name' => $fileName,
            'path'      => $relative,
            'size'      => (int) (@filesize($target) ?: 0),
        ], [
            'database'   => $database,
            'connection' => $connection,
        ]);
    }

    /** Müvəqqəti giriş faylı - yalnız cari istifadəçi oxuya bilir. */
    private function writeCredentialsFile(array $config): string
    {
        $file = tempnam(sys_get_temp_dir(), 'gopanel-dump-');

        if ($file === false) {
            throw new RuntimeException('Müvəqqəti fayl yaradıla bilmədi.');
        }

        @chmod($file, 0600);

        $contents = "[client]\n"
            . 'user=' . $config['username'] . "\n"
            . 'password="' . str_replace('"', '\"', (string) $config['password']) . "\"\n"
            . 'host=' . ($config['host'] ?? '127.0.0.1') . "\n"
            . 'port=' . ($config['port'] ?? 3306) . "\n";

        file_put_contents($file, $contents);

        return $file;
    }

    private function dump(string $cnfFile, string $database, string $rawFile): void
    {
        $command = array_merge(
            [(string) config('gopanel.backup.mysqldump_binary', 'mysqldump')],
            ['--defaults-extra-file=' . $cnfFile],
            (array) config('gopanel.backup.mysqldump_options', []),
            [$database]
        );

        $handle = fopen($rawFile, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Dump faylı yaradıla bilmədi: ' . $rawFile);
        }

        $process = new Process($command);
        $process->setTimeout($this->timeout);

        // Çıxış birbaşa fayla yazılır - 60+ MB dump yaddaşda saxlanılmır
        $process->run(function (string $type, string $buffer) use ($handle): void {
            if ($type === Process::OUT) {
                fwrite($handle, $buffer);
            }
        });

        fclose($handle);

        if (!$process->isSuccessful()) {
            $error = trim($process->getErrorOutput()) ?: 'mysqldump xəta qaytardı';
            throw new RuntimeException($error);
        }

        if (!is_file($rawFile) || filesize($rawFile) === 0) {
            throw new RuntimeException('Dump boş çıxdı - mysqldump işləmədi.');
        }
    }

    /** Axınla sıxma - fayl bütöv halda yaddaşa alınmır. */
    private function compress(string $rawFile, string $target): void
    {
        $in  = fopen($rawFile, 'rb');
        $out = gzopen($target, 'wb9');

        if ($in === false || $out === false) {
            throw new RuntimeException('Arxiv yaradıla bilmədi.');
        }

        while (!feof($in)) {
            $chunk = fread($in, 1024 * 512);

            if ($chunk === false) {
                break;
            }

            gzwrite($out, $chunk);
        }

        fclose($in);
        gzclose($out);
    }
}
