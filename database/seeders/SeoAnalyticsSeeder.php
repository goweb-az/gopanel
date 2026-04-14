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
            // Bütün axtarış sistemlərinə icazə verən robots.txt məzmunu
            'robots_txt' => <<<TXT
User-agent: *
Allow:
TXT,
            // Ən çox istifadə olunan AI botlarına açıq icazə verən qaydalar
            'ai_txt' => <<<TXT
User-agent: *
Allow: /

Usage-Policy: all

User-agent: GPTBot
Allow: /

User-agent: CCBot
Allow: /

User-agent: Ai2Bot
Allow: /

User-agent: Google-Extended
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: PerplexityBot
Allow: /
TXT,
            'other' => null,
        ]);
    }
}
