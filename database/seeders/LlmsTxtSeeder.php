<?php

namespace Database\Seeders;

use App\Models\Seo\LlmsTxt;
use Illuminate\Database\Seeder;

class LlmsTxtSeeder extends Seeder
{
    public function run(): void
    {
        if (LlmsTxt::count() > 0) {
            return;
        }

        LlmsTxt::create([
            'content' => $this->defaultContent(),
        ]);
    }

    /**
     * Default mətn SeoMachineFilesSeeder-dədir - `/markdown-index.md`,
     * `/llms-full.txt` və sitemap ünvanlarını göstərir. Mövcud bazada köhnə
     * default-u yeniləmək üçün: db:seed --class=SeoMachineFilesSeeder.
     */
    private function defaultContent(): string
    {
        return (new SeoMachineFilesSeeder())->llms();
    }
}
