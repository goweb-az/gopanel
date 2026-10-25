<?php

namespace Tests\Feature\Site;

use App\Models\Geography\Language;
use App\Models\Seo\SeoAnalytics;
use App\Services\Seo\MachineFilesService;
use App\Services\Site\Seo\MetaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * `<head>` SEO teqləri və mətn formatlı maşın faylları.
 */
class SeoHeadAndMachineFilesTest extends TestCase
{
    use DatabaseTransactions;

    private function locale(): string
    {
        return Language::getCachedAll()->pluck('code')->first() ?? 'az';
    }

    private function homeHtml(): string
    {
        $response = $this->withCookie('__hs', '1')->get('/' . $this->locale());
        $response->assertOk();

        return $response->getContent();
    }

    /** Eyni adlı `robots` teqləri birləşdirilir - yalnız biri olmalıdır. */
    public function test_head_has_single_robots_tag(): void
    {
        $this->assertSame(1, substr_count($this->homeHtml(), '<meta name="robots"'));
    }

    public function test_head_has_og_and_hreflang_x_default(): void
    {
        $html = $this->homeHtml();

        $this->assertStringContainsString('property="og:locale"', $html);
        $this->assertStringContainsString('property="og:site_name"', $html);
        $this->assertStringContainsString('hreflang="x-default"', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
    }

    public function test_json_ld_graph_is_valid_json(): void
    {
        $html = $this->homeHtml();

        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m));

        $data = json_decode($m[1], true);
        $this->assertIsArray($data);
        $types = array_column($data['@graph'], '@type');
        $this->assertContains('WebSite', $types);
    }

    public function test_robots_txt_survives_missing_row(): void
    {
        SeoAnalytics::query()->delete();
        Cache::forget('seo_analytics');

        $this->withHeaders(['Accept' => '*/*'])->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('User-agent: *', false);

        $this->withHeaders(['Accept' => '*/*'])->get('/ai.txt')->assertOk();
        $this->withHeaders(['Accept' => '*/*'])->get('/ads.txt')->assertNotFound();
    }

    public function test_ads_txt_is_served_when_filled(): void
    {
        if (!Schema::hasColumn('seo_analytics', 'ads_txt')) {
            $this->markTestSkipped('ads_txt miqrasiyası işlədilməyib');
        }

        $item = SeoAnalytics::latest()->first() ?? new SeoAnalytics();
        $item->forceFill(['ads_txt' => 'google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0'])->save();
        Cache::forget('seo_analytics');

        $this->withHeaders(['Accept' => '*/*'])->get('/ads.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('pub-0000000000000000', false);
    }

    public function test_machine_files_list_covers_all_files(): void
    {
        $titles = array_column(app(MachineFilesService::class)->links(), 'title');

        foreach (['robots.txt', 'ai.txt', 'ads.txt', 'llms.txt', 'llms-full.txt', 'markdown-index.md', 'sitemap.xml', 'rss-index.opml'] as $file) {
            $this->assertContains($file, $titles);
        }
    }

    public function test_admin_machine_files_block_renders(): void
    {
        $html = view('gopanel.pages.seo.seo-analytics.partials.machine-files', [
            'machineFiles' => app(MachineFilesService::class)->links(),
        ])->render();

        $this->assertStringContainsString('Maşın faylları', $html);
        $this->assertStringContainsString(url('/llms-full.txt'), $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('style=', $html);
    }

    /** Seeder köhnə default-u yeniləyir, paneldən yazılanı qorunur, təkrar işləmə heç nə dəyişmir. */
    public function test_machine_files_seeder_is_idempotent_and_keeps_manual_content(): void
    {
        $item = SeoAnalytics::latest()->first() ?? SeoAnalytics::create([]);
        $item->forceFill(['robots_txt' => "User-agent: *\r\nAllow:", 'ai_txt' => 'Admin yazıb'])->save();

        $seeder = new \Database\Seeders\SeoMachineFilesSeeder();
        $seeder->run();

        $item->refresh();
        $this->assertStringContainsString('markdown-index.md', $item->robots_txt);
        $this->assertSame('Admin yazıb', $item->ai_txt);

        $before = $item->robots_txt;
        $seeder->run();
        $this->assertSame($before, $item->fresh()->robots_txt);
    }

    public function test_meta_service_keeps_legacy_api_and_adds_new_methods(): void
    {
        $service = app(MetaService::class);

        foreach (['compose', 'composeAndShare', 'composeForRequest', 'composeForModel', 'composeFromArray'] as $method) {
            $this->assertTrue(method_exists($service, $method), $method);
        }

        $meta = $service->composeFromArray(['title' => 'Test başlıq', 'description' => '<p>Təsvir</p>']);

        $this->assertSame('Test başlıq', $meta['title']);
        $this->assertSame('Təsvir', $meta['description']);
        $this->assertArrayHasKey('site_title', $meta);
    }
}
