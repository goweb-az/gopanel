<?php

namespace Tests\Feature\Site;

use App\Models\Geography\Language;
use Tests\TestCase;

/**
 * Sitemap və RSS çıxışları.
 *
 * Bu fayllar brauzerdə açılmır - səhv edəndə heç kim görmür, sadəcə axtarış
 * sistemi feed-i oxumur. Ona görə yoxlama avtomatikdir: XML düzgün qurulubmu
 * (prolog ən əvvəldə), sətirlər tam URL-dirmi, route adları dəyişməyibmi.
 */
class FeedEndpointsTest extends TestCase
{
    /** @return array<int, string> */
    private function locales(): array
    {
        return Language::getCachedAll()->pluck('code')->all();
    }

    private function xml(string $uri): \SimpleXMLElement
    {
        $response = $this->withHeaders(['Accept' => '*/*'])->get($uri);
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $content = $response->getContent();
        $this->assertStringStartsWith('<?xml', $content, "XML prologu ən əvvəldə deyil: {$uri}");

        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml, "XML sınıqdır: {$uri}");

        return $xml;
    }

    public function test_route_names_are_kept(): void
    {
        $this->assertSame(url('/sitemap.xml'), route('sitemap.index'));
        $this->assertSame(url('/rss-index.opml'), route('site.rss.index'));

        foreach ($this->locales() as $locale) {
            $this->assertSame(url("{$locale}/sitemap.xml"), route("site.{$locale}.sitemap.single"));
            $this->assertSame(url("{$locale}/rss.xml"), route("site.{$locale}.rss.single"));
        }
    }

    public function test_sitemap_index_lists_every_active_language(): void
    {
        $xml  = $this->xml('/sitemap.xml');
        $locs = [];

        foreach ($xml->sitemap as $sitemap) {
            $locs[] = (string) $sitemap->loc;
        }

        foreach ($this->locales() as $locale) {
            $this->assertContains(route("site.{$locale}.sitemap.single"), $locs);
        }
    }

    public function test_every_language_sitemap_is_valid(): void
    {
        foreach ($this->locales() as $locale) {
            $xml = $this->xml("/{$locale}/sitemap.xml");

            $this->assertGreaterThan(0, count($xml->url), "{$locale} sitemap-ı boşdur");
            $this->assertSame(url($locale), (string) $xml->url[0]->loc, 'İlk sətir ana səhifədir');

            foreach ($xml->url as $url) {
                $this->assertStringStartsWith('http', (string) $url->loc);
                $this->assertNotEmpty((string) $url->lastmod);
                $this->assertStringStartsNotWith('/gopanel', (string) parse_url((string) $url->loc, PHP_URL_PATH));
            }
        }
    }

    public function test_rss_feed_is_valid_for_every_language(): void
    {
        foreach ($this->locales() as $locale) {
            $xml = $this->xml("/{$locale}/rss.xml");

            $this->assertSame($locale, (string) $xml->channel->language);
            $this->assertNotEmpty((string) $xml->channel->title);

            foreach ($xml->channel->item as $item) {
                $this->assertNotEmpty((string) $item->title);
                $this->assertStringStartsWith('http', (string) $item->link);
            }
        }
    }

    public function test_opml_lists_every_feed(): void
    {
        $xml  = $this->xml('/rss-index.opml');
        $feed = (string) $xml->body->outline->asXML();

        foreach ($this->locales() as $locale) {
            $this->assertStringContainsString(route("site.{$locale}.rss.single"), $feed);
        }
    }

    /**
     * Default robots.txt (paneldə yazılmayanda) sitemap-ı göstərməlidir -
     * botlar onu ilk oradan tapır. Paneldən yazılmış mətn admin-indir, ona
     * görə burada default-un özü yoxlanılır.
     */
    public function test_default_robots_txt_points_to_the_sitemap(): void
    {
        $this->assertStringContainsString(
            'Sitemap: ' . url('/sitemap.xml'),
            app(\App\Services\Seo\MachineFilesService::class)->defaultRobots()
        );

        $this->withHeaders(['Accept' => '*/*'])->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }
}
