<?php

namespace Database\Seeders\mock;

use App\Models\Geography\Language;
use App\Models\Translations\Translation;
use Database\Seeders\mock\Concerns\HasSeederProgress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TranslationSeeder extends Seeder
{
    use HasSeederProgress;

    public string $mockName = 'Tercumeler';

    public function run()
    {
        $dir = database_path('seeders/json-data/translations');

        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $files = collect(File::files($dir))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.json'))
            ->values();

        if ($files->isEmpty()) {
            $this->command?->warn("  JSON faylı tapılmadı: {$dir}");
            return;
        }

        $allowedLocales = Language::pluck('code')->all();

        Translation::query()
            ->whereNotIn('locale', $allowedLocales)
            ->delete();

        $this->progressStart($files->count());
        $total = 0;

        foreach ($files as $file) {
            $json         = File::get($file->getPathname());
            $translations = json_decode($json, true);

            if (empty($translations)) {
                $this->command?->warn("  Bos/parse edilmedi: {$file->getFilename()}");
                $this->progressTick("skip: {$file->getFilename()}");
                continue;
            }

            $fileCount = 0;
            foreach ($translations as $data) {
                if (!in_array($data['locale'] ?? null, $allowedLocales, true)) {
                    continue;
                }

                $platform = $data['platform'] ?? 'website';
                $page     = $data['page']     ?? 'general';
                $group    = $data['group']     ?? 'general';
                $filename = $data['filename']  ?? $platform;

                Translation::updateOrCreate(
                    [
                        'locale'   => $data['locale'],
                        'key'      => $data['key'],
                        'platform' => $platform,
                        'page'     => $page,
                        'group'    => $group,
                        'filename' => $filename,
                    ],
                    [
                        'value'      => $data['value'] ?? null,
                        'deleted_at' => $data['deleted_at'] ?? null,
                    ]
                );

                $fileCount++;
                $total++;
            }

            $this->progressTick("{$file->getFilename()} ({$fileCount} sətir)");
        }

        $this->progressFinish("Tamamlandi! {$total} tercume elave edildi.");
    }
}
