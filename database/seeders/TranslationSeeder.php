<?php

namespace Database\Seeders;

use App\Models\Translations\Translation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = database_path('seeders/json-data/translations.json');

        if (!File::exists($path)) {
            $this->command->error("JSON faylı tapılmadı: {$path}");
            return;
        }

        $json           = File::get($path);
        $translations   = json_decode($json, true);
        foreach ($translations as $data) {
            Translation::updateOrCreate(
                [
                    'locale' => $data['locale'],
                    'key' => $data['key'],
                    'group' => $data['group'],
                    'platform' => $data['platform'],
                ],
                [
                    'value' => $data['value'],
                    'filename' => $data['filename'],
                    'deleted_at' => $data['deleted_at'],
                ]
            );
            // $this->command->info("{$data['key']} [{$data['locale']}] dilində əlavə edildi ");
        }

        $this->command->info("Bütün təecümələr əlavə edildi!");
    }
}
