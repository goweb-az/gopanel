<?php

namespace Tests\Feature\Site;

use App\Models\Geography\Language;
use App\Models\Site\Blog;
use App\Services\Seo\Markdown\HtmlToMarkdown;
use App\Services\Seo\PublicContentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Səhifələrin Markdown variantı (`canonical + .md`), `/markdown-index.md`,
 * `/llms-full.txt` və HTML-dəki alternate keçidlər.
 *
 * Əsas qayda: Markdown HTML saytdan ÇOX məzmun açmır - deaktiv qeyd 404-dür.
 * Yazan testlər tranzaksiya içindədir, baza dəyişmir.
 */
class MarkdownPagesTest extends TestCase
{
    use DatabaseTransactions;

    private function locale(): string
    {
        return Language::getCachedAll()->pluck('code')->first() ?? 'az';
    }

    private function md(string $uri)
    {
        return $this->withHeaders(['Accept' => '*/*'])->get($uri);
    }

    private function makeBlog(bool $active, string $slug): Blog
    {
        $blog = Blog::create(['is_active' => $active, 'image' => 'assets/test.png', 'date_time' => now()->toDateTimeString()]);

        $blog->translations()->create(['locale' => $this->locale(), 'key' => 'title', 'value' => 'Markdown test yazısı']);
        $blog->translations()->create(['locale' => $this->locale(), 'key' => 'description', 'value' => '<p>Salam <strong>dünya</strong></p>']);
        $blog->translations()->create(['locale' => $this->locale(), 'key' => 'slug', 'value' => $slug]);

        return $blog->fresh();
    }

    public function test_home_markdown_has_headers_and_canonical(): void
    {
        $locale   = $this->locale();
        $response = $this->md("/{$locale}.md");

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertHeader('Link', '<' . url($locale) . '>; rel="canonical"');

        $this->assertStringStartsWith('# ', $response->getContent());
        $this->assertStringContainsString(url('/markdown-index.md'), $response->getContent());
    }

    public function test_menu_pages_have_markdown_variant(): void
    {
        $locale = $this->locale();
        $menus  = app(PublicContentService::class)->menuPages($locale);

        foreach ($menus as $menu) {
            $this->md("/{$locale}/{$menu->route_slug}.md")->assertOk();
        }

        $this->assertTrue(true);
    }

    public function test_active_blog_renders_and_inactive_blog_is_404(): void
    {
        $locale = $this->locale();

        $this->makeBlog(true, 'md-aktiv-yazi-test');
        $this->makeBlog(false, 'md-deaktiv-yazi-test');

        $active = $this->md("/{$locale}/md-aktiv-yazi-test.md");
        $active->assertOk();
        $this->assertStringContainsString('# Markdown test yazısı', $active->getContent());
        $this->assertStringContainsString('**dünya**', $active->getContent());

        $this->md("/{$locale}/md-deaktiv-yazi-test.md")->assertNotFound();
        $this->md("/{$locale}/bele-sehife-yoxdur-xyz.md")->assertNotFound();
        $this->md('/zz/whatever.md')->assertNotFound();
    }

    public function test_index_lists_only_public_items(): void
    {
        $this->makeBlog(true, 'md-indeks-aktiv');
        $this->makeBlog(false, 'md-indeks-deaktiv');

        $content = $this->md('/markdown-index.md')->assertOk()->getContent();

        $this->assertStringContainsString('md-indeks-aktiv.md', $content);
        $this->assertStringNotContainsString('md-indeks-deaktiv', $content);
    }

    public function test_llms_full_concatenates_documents(): void
    {
        $this->makeBlog(true, 'md-full-aktiv');

        $response = $this->md('/llms-full.txt');
        $response->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $this->assertStringContainsString('md-full-aktiv', $response->getContent());
        $this->assertStringContainsString("\n---\n", $response->getContent());
    }

    public function test_llms_full_can_be_disabled(): void
    {
        config(['seo.markdown.llms_full.enabled' => false]);

        $this->md('/llms-full.txt')->assertNotFound();
    }

    public function test_excluded_and_technical_paths_have_no_markdown_url(): void
    {
        $locale = $this->locale();

        $this->assertNull(markdown_url('/gopanel/dashboard'));
        $this->assertNull(markdown_url("/{$locale}/sitemap.xml"));
        $this->assertNull(markdown_url("/{$locale}.md"));
        $this->assertSame(url($locale) . '.md', markdown_url("/{$locale}"));
    }

    public function test_html_page_advertises_markdown_alternate(): void
    {
        $locale   = $this->locale();
        $response = $this->withCookie('__hs', '1')->get("/{$locale}");

        $response->assertOk();
        $this->assertStringContainsString('type="text/markdown"', $response->getContent());
        $this->assertStringContainsString('rel="alternate"; type="text/markdown"', (string) $response->headers->get('Link'));
    }

    public function test_html_to_markdown_converter(): void
    {
        $md = app(HtmlToMarkdown::class)->convert('<h2>Başlıq</h2><p>Mətn <a href="https://x.az">keçid</a></p><ul><li>bir</li></ul>');

        $this->assertStringContainsString('## Başlıq', $md);
        $this->assertStringContainsString('[keçid](https://x.az)', $md);
        $this->assertStringContainsString('- bir', $md);
    }
}
