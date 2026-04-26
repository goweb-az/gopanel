<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RestoreGeoIpDatabases extends Command
{
    protected $signature = 'geoip:restore {--force : Overwrite existing files}';
    protected $description = 'Restore missing GeoLite2 database files from backup';

    public function handle(): int
    {
        $items = [
            'GeoLite2-ASN'     => 'GeoLite2-ASN.mmdb',
            'GeoLite2-City'    => 'GeoLite2-City.mmdb',
            'GeoLite2-Country' => 'GeoLite2-Country.mmdb',
        ];

        $force = $this->option('force');

        foreach ($items as $folder => $file) {

            $targetDir  = database_path("geo-data/{$folder}");
            $targetFile = "{$targetDir}/{$file}";

            $backupFile = database_path("backups/geo-data/{$folder}/{$file}");

            if (! file_exists($backupFile)) {
                $this->error("Backup not found: {$backupFile}");
                continue;
            }

            File::ensureDirectoryExists($targetDir);

            if (file_exists($targetFile) && ! $force) {
                $this->line("Skipped (exists): {$file}");
                continue;
            }

            File::copy($backupFile, $targetFile);

            if ($force && file_exists($targetFile)) {
                $this->info("Overwritten: {$file}");
            } else {
                $this->info("Restored: {$file}");
            }
        }

        return self::SUCCESS;
    }
}
