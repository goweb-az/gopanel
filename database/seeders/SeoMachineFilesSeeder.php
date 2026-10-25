<?php

namespace Database\Seeders;

use App\Models\Seo\LlmsTxt;
use App\Models\Seo\SeoAnalytics;
use App\Services\Seo\MachineFilesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Maşın üçün mətn fayllarının başlanğıc məzmunu: `/robots.txt`, `/ai.txt`, `/llms.txt`.
 *
 * ── Mənbələr (mövcud arxitektura - dəyişmir) ────────────────────────────
 *
 *   robots.txt → `seo_analytics.robots_txt`  (GoPanel → SEO → SEO və analitika)
 *   ai.txt     → `seo_analytics.ai_txt`      (eyni səhifə)
 *   llms.txt   → `llms_txts.content`         (GoPanel → SEO → LLMs.txt)
 *
 * Seeder yalnız **başlanğıc məzmun** yazır - redaktə yeri panel olaraq qalır.
 * Mətnlər `/markdown-index.md`, `/llms-full.txt`, sitemap və RSS ünvanlarını
 * göstərir ki, agentlər saytın maşın resurslarını ilk fayldan tapsın.
 *
 * ── İdempotentlik: əl ilə yazılmış məzmun qorunur ───────────────────────
 *
 * Qərar **sahə səviyyəsindədir** (`seo_analytics` eyni zamanda analitika
 * kodlarını saxlayır, `updated_at` onlara görə də dəyişir):
 *
 *   sahə boşdur                         → yazılır     (created)
 *   sahə cari default ilə eynidir       → toxunulmur  (unchanged)
 *   sahə əvvəlki seed default-udur      → yenilənir   (updated)
 *   qalan hallarda (paneldən yazılıb)   → ATLANIR     (skipped) + xəbərdarlıq
 *
 * «Əvvəlki seed default-ları» `previous*()` metodlarındadır - köhnə
 * `SeoAnalyticsSeeder`/`LlmsTxtSeeder` mətnləri. Seeder mətni dəyişəndə köhnə
 * variant ora əlavə olunur: seeder öz köhnə məzmununu tanıyıb yeniləyir,
 * admin-in yazdığını isə yox. Müqayisədə domen `{BASE}` ilə əvəzlənir - lokal
 * və production eyni nəticə verir.
 *
 * Bilərəkdən default-a qayıtmaq üçün: paneldə sahəni boşaldıb saxla, sonra
 * `php artisan db:seed --class=SeoMachineFilesSeeder`.
 *
 * Domen hardcode edilmir - hər ünvan `config('app.url')`-dan qurulur.
 */
class SeoMachineFilesSeeder extends Seeder
{
    /** @var array<int, array{0: string, 1: string, 2: string}> fayl, nəticə, qeyd */
    private array $results = [];

    public function run(): void
    {
        if (Schema::hasTable('seo_analytics')) {
            $this->seedAnalyticsField('robots.txt', 'robots_txt', $this->robots(), $this->previousRobots());
            $this->seedAnalyticsField('ai.txt', 'ai_txt', $this->ai(), $this->previousAi());
            Cache::forget('seo_analytics');
        }

        if (Schema::hasTable('llms_txts')) {
            $this->seedLlms();
            Cache::forget('llms_txt');
        }

        $this->command?->table(['Fayl', 'Nəticə', 'Qeyd'], $this->results);
    }

    /* ── Default mətnlər (SeoAnalyticsSeeder / LlmsTxtSeeder də işlədir) ── */

    public function robots(): string
    {
        return "# LLM information: " . $this->url('/llms.txt') . "\n"
            . "# Markdown index: " . $this->url('/markdown-index.md') . "\n"
            . app(MachineFilesService::class)->defaultRobots();
    }

    /**
     * `ai.txt` üçün ümumi qəbul edilmiş standart yoxdur - köhnə default-dakı
     * bot icazələri saxlanılır, üstünə maşın resurslarının siyahısı (şərh kimi)
     * əlavə olunur.
     */
    public function ai(): string
    {
        return implode("\n", [
            '# ' . $this->appName(),
            '# Website: ' . $this->url('/'),
            '# LLM information: ' . $this->url('/llms.txt'),
            '# Full text (Markdown): ' . $this->url('/llms-full.txt'),
            '# Markdown index: ' . $this->url('/markdown-index.md'),
            '# Append `.md` to any canonical page URL for its Markdown version.',
            '# Sitemap: ' . $this->url('/sitemap.xml'),
            '',
            $this->legacyAiRules(),
            '',
        ]);
    }

    public function llms(): string
    {
        $appName = $this->appName();
        $appUrl  = $this->url('/');

        return <<<TXT
# {$appName}

> {$appName} veb saytı haqqında məlumat.

## Sayt haqqında
- Ad: {$appName}
- URL: {$appUrl}

## Markdown məzmun
Hər publik səhifənin ünvanına `.md` əlavə etdikdə həmin səhifənin Markdown variantı açılır (məs. {$this->url('/az.md')}).

- [Markdown indeksi]({$this->url('/markdown-index.md')}) - bütün `.md` ünvanları, dillər üzrə
- [Tam mətn]({$this->url('/llms-full.txt')}) - bütün səhifələr bir faylda

## Maşın resursları
- [Sitemap]({$this->url('/sitemap.xml')})
- [RSS (OPML)]({$this->url('/rss-index.opml')})
- [robots.txt]({$this->url('/robots.txt')})
- [ai.txt]({$this->url('/ai.txt')})

## İstifadə qaydaları
Bu saytdakı məzmunun AI/LLM sistemləri tərəfindən istifadəsi icazəlidir, lakin mənbə göstərilməlidir. İstinad edərkən səhifənin canonical (HTML) ünvanını göstərin.

TXT;
    }

    /* ── Əvvəlki seed default-ları ──────────────────────────────────────── */

    /** @return array<int, string> */
    private function previousRobots(): array
    {
        return [
            // SeoAnalyticsSeeder v1
            "User-agent: *\nAllow:",
            // TxtController-in köhnə ehtiyat mətni
            app(MachineFilesService::class)->defaultRobots(),
        ];
    }

    /** @return array<int, string> */
    private function previousAi(): array
    {
        return [$this->legacyAiRules()];
    }

    /** @return array<int, string> */
    private function previousLlms(): array
    {
        $appName = $this->appName();
        $appUrl  = (string) config('app.url', 'https://example.com');

        // LlmsTxtSeeder v1
        return [<<<TXT
# {$appName}

> {$appName} veb saytı haqqında məlumat.

## Sayt haqqında
- Ad: {$appName}
- URL: {$appUrl}
- Dillər: Azərbaycan, English, Русский

## Əlaqə
- Veb sayt: {$appUrl}

## İstifadə qaydaları
Bu saytdakı məzmunun AI/LLM sistemləri tərəfindən istifadəsi icazəlidir, lakin mənbə göstərilməlidir.
TXT];
    }

    /** SeoAnalyticsSeeder v1-in ai.txt mətni (bot icazələri). */
    private function legacyAiRules(): string
    {
        return <<<TXT
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
TXT;
    }

    /* ── Yazma ──────────────────────────────────────────────────────────── */

    /**
     * @param  array<int, string>  $previous
     */
    private function seedAnalyticsField(string $label, string $column, string $default, array $previous): void
    {
        $item = SeoAnalytics::latest()->first();

        if ($item === null) {
            SeoAnalytics::create([$column => $default]);
            $this->results[] = [$label, 'created', 'seo_analytics sətri yaradıldı'];

            return;
        }

        $this->apply($item, $column, $default, $previous, $label);
    }

    private function seedLlms(): void
    {
        $item = LlmsTxt::latest()->first();

        if ($item === null) {
            LlmsTxt::create(['content' => $this->llms()]);
            $this->results[] = ['llms.txt', 'created', 'llms_txts sətri yaradıldı'];

            return;
        }

        $this->apply($item, 'content', $this->llms(), $this->previousLlms(), 'llms.txt');
    }

    /**
     * @param  array<int, string>  $previous
     */
    private function apply($item, string $column, string $default, array $previous, string $label): void
    {
        $decision = $this->decide($item->{$column}, $default, $previous);

        if ($decision === 'unchanged') {
            $this->results[] = [$label, 'unchanged', 'məzmun artıq aktualdır'];

            return;
        }

        if ($decision === 'skip') {
            $this->results[] = [$label, 'skipped', 'paneldən redaktə edilib - qorunur'];
            $this->command?->warn("  {$label}: paneldən redaktə edilib, üzərinə yazılmadı.");

            return;
        }

        $wasEmpty = $this->normalize($item->{$column}) === '';
        $item->forceFill([$column => $default])->save();

        $this->results[] = [$label, $wasEmpty ? 'created' : 'updated', $item->getTable() . '.' . $column];
    }

    /**
     * @param  array<int, string>  $previous
     * @return 'write'|'unchanged'|'skip'
     */
    private function decide(?string $current, string $default, array $previous): string
    {
        $value = $this->normalize($current);

        if ($value === '') {
            return 'write';
        }

        if ($value === $this->normalize($default)) {
            return 'unchanged';
        }

        foreach ($previous as $known) {
            if ($value === $this->normalize($known)) {
                return 'write';
            }
        }

        return 'skip';
    }

    /**
     * Sətir sonu, kənar boşluqlar və domen müqayisəyə təsir etməsin.
     */
    private function normalize(?string $value): string
    {
        $value = trim(str_replace("\r\n", "\n", (string) $value));
        $base  = rtrim((string) config('app.url'), '/');

        return $base !== '' ? str_replace($base, '{BASE}', $value) : $value;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('app.url'), '/') . ($path === '/' ? '' : $path);
    }

    private function appName(): string
    {
        return (string) config('app.name', 'Gopanel');
    }
}
