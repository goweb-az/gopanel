<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Seo\SeoAnalytics;

class SeoAnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Əgər cədvəldə artıq məlumat varsa, əlavə etmə
        if (SeoAnalytics::query()->exists()) {
            return;
        }

        $machineFiles = new SeoMachineFilesSeeder();

        SeoAnalytics::create([
            'head' => <<<HTML
<!-- HEAD Analytics -->
<!-- Buraya <head> bölməsinə yerləşdiriləcək analytics/pixel kodlarını əlavə edin -->
HTML,
            'body' => <<<HTML
<!-- BODY Analytics -->
<!-- Lazım gələrsə <body> daxilində (noscript və s.) istifadə olunacaq kodları əlavə edin -->
HTML,
            'footer' => <<<HTML
<!-- FOOTER Analytics -->
<!-- Səhifənin sonunda yerləşdiriləcək izləmə/skript kodları -->
HTML,
            // robots.txt / ai.txt default-ları SeoMachineFilesSeeder-dədir (sitemap,
            // llms.txt, /markdown-index.md ünvanlarını göstərir). Mövcud bazada
            // köhnə mətni yeniləmək üçün: db:seed --class=SeoMachineFilesSeeder
            'robots_txt' => $machineFiles->robots(),
            'ai_txt'     => $machineFiles->ai(),
            'other' => null,
        ]);
    }
}
