<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Services\Cache\CacheService;
use App\Support\Cache\CacheKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * `/{dil}/rss.xml` üçün yazı siyahısını qurur.
 *
 * Yalnız **tarixli məzmun** feed-ə düşür - `config/seo/content.php`-də
 * `rss => true` olan modellər (bloq, xəbər). Statik səhifələr abunəçi üçün
 * «yeni yazı» deyil - onlar sitemap-dadır.
 *
 * NİYƏ servis: əvvəlki controller `Cache::remember("rss_items_{$locale}", 12 saat)`
 * işlədirdi - paneldə yeni yazı feed-də yarım gün görünmürdü. İndi açar keş
 * qruplarının versiyasını daşıyır və məzmun dəyişəndə özü köhnəlir. Bir neçə
 * mənbə qarışdığı üçün sıralama da burada olur, blade-də yox.
 */
class RssService
{
    public function __construct(private readonly PublicContentService $content)
    {
    }

    /**
     * @return array<int, array{title: string, description: string, link: string, guid: string, pubDate: ?string, timestamp: int}>
     */
    public function items(string $locale): array
    {
        return CacheService::remember(
            CacheKey::seo('rss', ['l' => $locale, 'fp' => $this->content->fingerprint()]),
            fn (): array => $this->build($locale),
        );
    }

    /**
     * Kanal məlumatı - `<channel>` başlığı.
     *
     * @return array{title: string, link: string, description: string, language: string, lastBuild: string, self: string}
     */
    public function channel(string $locale, string $selfUrl): array
    {
        $description = '';

        foreach ($this->content->menuPages($locale) as $menu) {
            foreach ($this->content->models() as $options) {
                if (($options['rss'] ?? false) && ($options['list_menu'] ?? null) === $menu->route_name) {
                    $description = $this->content->menuTitle($menu, $locale);
                    break 2;
                }
            }
        }

        return [
            'title'       => config('app.name') . ' - ' . strtoupper($locale),
            'link'        => url($locale),
            'description' => $description !== '' ? $description : (string) config('app.name'),
            'language'    => $locale,
            'lastBuild'   => now()->toRfc2822String(),
            'self'        => $selfUrl,
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, link: string, guid: string, pubDate: ?string, timestamp: int}>
     */
    private function build(string $locale): array
    {
        $items = [];

        foreach ($this->content->models() as $class => $options) {
            if (!($options['rss'] ?? false)) {
                continue;
            }

            foreach ($this->content->items($class) as $item) {
                $entry = $this->entry($item, $locale);

                if ($entry !== null) {
                    $items[] = $entry;
                }
            }
        }

        // Ən yenisi başda - mənbələr qarışdığı üçün sıralama burada olur
        usort($items, fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($items, 0, max(1, (int) config('seo.content.rss_limit', 50)));
    }

    /**
     * @return array{title: string, description: string, link: string, guid: string, pubDate: ?string, timestamp: int}|null
     */
    private function entry(Model $item, string $locale): ?array
    {
        $link  = $this->content->isRoutable($item) ? $this->content->url($item, $locale) : null;
        $title = $this->content->title($item, $locale);

        // Slug yoxdursa link də yoxdur; başlıqsız yazı oxucuya heç nə demir
        if ($link === null || $title === null) {
            return null;
        }

        $date = $this->content->publishedAt($item);

        return [
            'title'       => $title,
            'description' => $this->summary(
                $this->content->translated($item, 'short_description', $locale)
                ?? $this->content->translated($item, 'description', $locale)
            ),
            'link'        => $link,
            'guid'        => $link,
            'pubDate'     => $date?->toRfc2822String(),
            'timestamp'   => $date?->getTimestamp() ?? 0,
        ];
    }

    /**
     * Redaktordan gələn HTML feed oxuyucusuna düz mətn kimi verilir - teqlər
     * təmizlənir, HTML entity-ləri (`&nbsp;`) həqiqi simvola çevrilir.
     */
    private function summary(?string $description): string
    {
        if ($description === null) {
            return '';
        }

        $text = html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(preg_replace('/\s+/u', ' ', trim($text)) ?? '', 400);
    }
}
