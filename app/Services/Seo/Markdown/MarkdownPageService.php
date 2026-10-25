<?php

declare(strict_types=1);

namespace App\Services\Seo\Markdown;

use App\Models\Contact\ContactInfo;
use App\Models\Navigation\Menu;
use App\Models\Settings\SiteSetting;
use App\Services\Cache\CacheService;
use App\Services\Seo\PublicContentService;
use App\Support\Cache\CacheKey;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * Publik səhifələrin maşın (AI agent, LLM) üçün Markdown variantı.
 *
 * Ünvan qaydası: **canonical URL-in sonuna `.md`** -
 * `/az/bloqlar` → `/az/bloqlar.md`, ana səhifə `/az` → `/az.md`.
 * Bütün siyahını `/markdown-index.md`, bütün mətni bir faylda `/llms-full.txt` verir.
 *
 * NİYƏ: agentlər HTML-i (menyu, footer, skript) təmizləməyə token xərcləyir və
 * tez-tez səhv kəsir. Markdown variantı eyni məzmunu təmiz verir və öz canonical
 * ünvanını bildirir - agent istinad edəndə HTML ünvanı göstərir.
 *
 * Yeni HTML şablonu yaradılmır: mətn mövcud modellərdən oxunur, redaktor
 * HTML-i `HtmlToMarkdown` ilə çevrilir. Yəni Markdown variantı ilə sayt
 * həmişə eyni məzmunu göstərir.
 *
 * ── Əhatə (gopanel URL qəlibi) ───────────────────────────────────────────
 *
 *   /{dil}.md                ana səhifə (sayt təsviri + bölmələr)
 *   /{dil}/{menyu-slug}.md   menyu səhifəsi (`config/site/menu_routes.php`-də route-u olan)
 *                            + siyahı modeli varsa (`config/seo/content.php` → list_menu) yazılar
 *   /{dil}/{slug}.md         məzmun qeydi (`DynamicContentController`-in açdığı hər model)
 *
 * Deaktiv/silinmiş qeyd, route-u olmayan menyu və `excluded_prefixes`
 * (`config/seo/markdown.php`) **404** verir - Markdown HTML-dən çox məzmun açmır.
 *
 * ── Keş ─────────────────────────────────────────────────────────────────
 *
 * Hazır sənəd `CacheKey::seo('markdown', …)` altındadır: açar `seo` qrupunun
 * versiyasını VƏ `PublicContentService::fingerprint()`-i (bütün keş qruplarının
 * versiyaları: page, menu, settings...) daşıyır - paneldə məzmun dəyişəndə
 * `CacheInvalidationServiceProvider` qrupu artırır və sənəd özü köhnəlir,
 * ayrıca invalidation kodu yoxdur.
 * Yolun həlli (`supports()`) keşlənmir - aktivlik həmişə təzə yoxlanılır.
 */
final class MarkdownPageService
{
    public function __construct(
        private readonly HtmlToMarkdown $html,
        private readonly PublicContentService $content,
    ) {
    }

    /**
     * Verilmiş dil və yol üçün Markdown sənədi; səhifə yoxdursa `null`.
     */
    public function page(string $locale, string $path): ?string
    {
        $path   = trim($path, '/');
        $render = $this->resolve($locale, $path);

        if ($render === null) {
            return null;
        }

        return $this->cached('markdown', ['l' => $locale, 'p' => $path], $render);
    }

    /**
     * Səhifənin Markdown variantı varmı - HTML-dəki `<link rel="alternate">`
     * və `Link:` başlığı yalnız bu `true` olanda verilir, yəni sayt heç vaxt
     * 404-ə keçid vermir.
     */
    public function supports(string $locale, string $path): bool
    {
        try {
            return $this->resolve($locale, trim($path, '/')) !== null;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * `/markdown-index.md` - bütün dillər üzrə mövcud Markdown ünvanları.
     */
    public function index(): string
    {
        return $this->cached('markdown-index', [], fn (): string => $this->buildIndex());
    }

    /**
     * `/llms-full.txt` - bütün Markdown sənədləri bir faylda.
     *
     * Bəzi agentlər saytı bir sorğu ilə «oxumaq» istəyir; indeksdəki hər ünvanı
     * ayrıca çəkmək əvəzinə hamısını bir fayldan alır. Sənəd sayı
     * `seo.markdown.llms_full.max_documents` ilə bağlanır.
     */
    public function full(): string
    {
        return $this->cached('llms-full', [], fn (): string => $this->buildFull());
    }

    /* ── Yolun həlli ────────────────────────────────────────────────────── */

    /**
     * Yolu səhifəyə bağlayır. Nəticə sənədi quran closure-dur - beləliklə
     * `supports()` heç nə render etmədən eyni məntiqi işlədir.
     */
    private function resolve(string $locale, string $path): ?Closure
    {
        if (!$this->content->isActiveLocale($locale) || $this->isExcluded($path)) {
            return null;
        }

        if ($path === '') {
            return fn (): string => $this->home($locale);
        }

        // gopanel URL qəlibi tək seqmentlidir: /{dil}/{slug}
        if (str_contains($path, '/')) {
            return null;
        }

        if ($menu = $this->content->findMenu($path, $locale)) {
            return fn (): string => $this->menuPage($menu, $locale);
        }

        if ($item = $this->content->findBySlug($path, $locale)) {
            return fn (): string => $this->article($item, $locale, $path);
        }

        return null;
    }

    private function isExcluded(string $path): bool
    {
        $first = explode('/', $path)[0] ?? '';

        return $first !== '' && in_array($first, (array) config('seo.markdown.excluded_prefixes', []), true);
    }

    /* ── Səhifələr ──────────────────────────────────────────────────────── */

    private function home(string $locale): string
    {
        $meta  = $this->siteMeta($locale);
        $lines = [
            '# ' . $meta['title'],
            '',
            $this->meta($locale, ''),
            '',
        ];

        if ($meta['description'] !== '') {
            $lines[] = $meta['description'];
            $lines[] = '';
        }

        $menus = $this->content->menuPages($locale);

        if ($menus->isNotEmpty()) {
            $lines[] = '## ' . $this->label($locale, 'sections');
            $lines[] = '';

            foreach ($menus as $menu) {
                $lines[] = $this->pageLink($this->content->menuTitle($menu, $locale), $locale, (string) $menu->route_slug);
            }

            $lines[] = '';
        }

        $lines[] = '- ' . $this->label($locale, 'full_index') . ': ' . url('/markdown-index.md');

        return $this->finish($lines);
    }

    private function menuPage(Menu $menu, string $locale): string
    {
        $path  = (string) $menu->route_slug;
        $lines = [
            '# ' . $this->content->menuTitle($menu, $locale),
            '',
            $this->meta($locale, $path),
        ];

        $description = $menu->route_description ?: $this->content->translated($menu, 'description', $locale);

        if ($description) {
            $lines[] = '';
            $lines[] = $this->html->convert($description);
        }

        $block = config('seo.markdown.menu_blocks.' . $menu->route_name);

        if ($block === 'contact') {
            $lines = array_merge($lines, $this->contactBlock($locale));
        }

        $class = $this->content->modelForMenu($menu);

        if ($class !== null) {
            $links = $this->itemLinks($this->content->items($class), $locale);

            if ($links !== []) {
                $lines[] = '';
                $lines[] = '## ' . $this->label($locale, 'items');
                $lines[] = '';
                $lines   = array_merge($lines, $links);
            }
        }

        return $this->finish($lines);
    }

    /**
     * Əlaqə səhifəsi - yalnız paneldə publik göstərilən məlumatlar. Forma və
     * xəritə maşın sənədində yoxdur.
     *
     * @return array<int, string>
     */
    private function contactBlock(string $locale): array
    {
        $contact = ContactInfo::getCached();

        if ($contact === null) {
            return [];
        }

        $rows = [];

        foreach ([
            'address' => $this->content->translated($contact, 'adress', $locale),
            'phone'   => $contact->phone,
            'mobile'  => $contact->mobile,
            'email'   => $contact->info_email ?: $contact->support_email,
        ] as $key => $value) {
            $value = trim(strip_tags((string) $value));

            if ($value !== '') {
                $rows[] = '- ' . $this->label($locale, $key) . ': ' . $value;
            }
        }

        return $rows === [] ? [] : array_merge(['', '## ' . $this->label($locale, 'contact'), ''], $rows);
    }

    /**
     * Məzmun qeydi (bloq yazısı və `config/seo/content.php`-dəki hər model):
     * başlıq, meta bloku, qısa təsvir, gövdə.
     */
    private function article(Model $item, string $locale, string $path): string
    {
        $date  = $this->content->publishedAt($item);
        $lines = [
            '# ' . ($this->content->title($item, $locale) ?? $path),
            '',
            $this->meta($locale, $path, $date?->format('Y-m-d')),
        ];

        foreach (['short_description', 'description'] as $field) {
            $value = $this->content->translated($item, $field, $locale);

            if ($value !== null) {
                $lines[] = '';
                $lines[] = $this->html->convert($value);
            }
        }

        return $this->finish($lines);
    }

    /* ── İndeks və tam mətn ─────────────────────────────────────────────── */

    private function buildIndex(): string
    {
        $site  = $this->siteMeta((string) config('app.locale', 'az'))['title'];
        $lines = [
            '# ' . $site . ' - Markdown index',
            '',
            'Machine-readable Markdown versions of the public pages on ' . url('/') . '.',
            'Append `.md` to any canonical page URL to get the same content as Markdown.',
            'Each entry links both the Markdown and the canonical HTML version.',
            '',
            '- LLM information: ' . url('/llms.txt'),
        ];

        if ($this->fullEnabled()) {
            $lines[] = '- Full text: ' . url('/llms-full.txt');
        }

        $lines[] = '- Sitemap index: ' . url('/sitemap.xml');

        foreach ($this->content->locales() as $locale) {
            $lines[] = '';
            $lines[] = '## ' . strtoupper($locale);
            $lines[] = '';
            $lines[] = '### ' . $this->label($locale, 'pages');
            $lines[] = '';
            $lines[] = $this->pageLink($this->label($locale, 'homepage'), $locale, '');

            foreach ($this->content->menuPages($locale) as $menu) {
                $lines[] = $this->pageLink($this->content->menuTitle($menu, $locale), $locale, (string) $menu->route_slug);
            }

            foreach ($this->content->models() as $class => $options) {
                $links = $this->itemLinks($this->content->items($class), $locale);

                if ($links !== []) {
                    $lines = array_merge($lines, ['', '### ' . $this->sectionTitle($class, $options, $locale), ''], $links);
                }
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function buildFull(): string
    {
        $limit = max(1, (int) config('seo.markdown.llms_full.max_documents', 300));
        $site  = $this->siteMeta((string) config('app.locale', 'az'))['title'];
        $docs  = [];

        foreach ($this->content->locales() as $locale) {
            $docs[] = $this->home($locale);

            foreach ($this->content->menuPages($locale) as $menu) {
                $docs[] = $this->menuPage($menu, $locale);
            }

            foreach (array_keys($this->content->models()) as $class) {
                foreach ($this->content->items($class) as $item) {
                    $slug = $this->content->slug($item, $locale);

                    if ($slug !== null && $this->content->isRoutable($item)) {
                        $docs[] = $this->article($item, $locale, $slug);
                    }

                    if (count($docs) >= $limit) {
                        break 3;
                    }
                }
            }
        }

        $header = implode("\n", [
            '# ' . $site . ' - full content',
            '',
            '> Every public page of ' . url('/') . ' as Markdown, in one file.',
            '> Each section states its own canonical URL. Index: ' . url('/markdown-index.md'),
            '',
        ]);

        return $header . "\n" . implode("\n---\n\n", array_slice($docs, 0, $limit));
    }

    /** İndeksdə model bölməsinin başlığı: siyahı menyusunun adı → sinif adı. */
    private function sectionTitle(string $class, array $options, string $locale): string
    {
        $menuRoute = $options['list_menu'] ?? null;

        if ($menuRoute) {
            $menu = $this->content->menuPages($locale)->first(fn (Menu $m): bool => $m->route_name === $menuRoute);

            if ($menu) {
                return $this->content->menuTitle($menu, $locale);
            }
        }

        return Str::headline(class_basename($class));
    }

    /* ── Köməkçilər ─────────────────────────────────────────────────────── */

    /**
     * Qeydlərin siyahısı - hər sətir həm Markdown, həm HTML ünvanını verir.
     *
     * @param  Collection<int, Model>  $items
     * @return array<int, string>
     */
    private function itemLinks(Collection $items, string $locale): array
    {
        $lines = [];

        foreach ($items as $item) {
            $slug = $this->content->slug($item, $locale);

            if ($slug === null || !$this->content->isRoutable($item)) {
                continue;
            }

            $line = $this->pageLink((string) ($this->content->title($item, $locale) ?? $slug), $locale, $slug);
            $date = $this->content->publishedAt($item);

            if ($date !== null) {
                $line .= ' - ' . $date->format('Y-m-d');
            }

            $lines[] = $line;
        }

        return $lines;
    }

    /** `- Başlıq - [Markdown](…md) | [HTML](…)` */
    private function pageLink(string $title, string $locale, string $path): string
    {
        $canonical = $this->canonical($locale, $path);

        return '- ' . $this->plain($title) . ' - [' . $this->label($locale, 'markdown') . '](' . $canonical . '.md)'
            . ' | [HTML](' . $canonical . ')';
    }

    /** Sənədin başındakı maşın üçün qısa məlumat bloku. */
    private function meta(string $locale, string $path, ?string $date = null): string
    {
        $canonical = $this->canonical($locale, $path);

        $lines = [
            '- ' . $this->label($locale, 'canonical') . ': ' . $canonical,
            '- ' . $this->label($locale, 'markdown') . ': ' . $canonical . '.md',
            '- ' . $this->label($locale, 'language') . ': ' . $locale,
        ];

        // Tarix yalnız qeydin özündə varsa - uydurulmuş «updated» yazılmır
        if ($date) {
            $lines[] = '- ' . $this->label($locale, 'date') . ': ' . $date;
        }

        return implode("\n", $lines);
    }

    /**
     * Saytın adı və təsviri: SiteSetting meta sətri → `config/seo/meta.php` → app.name.
     *
     * @return array{title: string, description: string}
     */
    private function siteMeta(string $locale): array
    {
        $title = '';
        $description = '';

        try {
            $settingsMeta = SiteSetting::getCached()?->meta($locale)->first();
            $title        = trim((string) ($settingsMeta->title ?? ''));
            $description  = $this->plain((string) ($settingsMeta->description ?? ''));
        } catch (Throwable) {
            // tənzimləmə cədvəli yoxdursa config dəyərlərinə düşülür
        }

        $defaults = (array) config("seo.meta.defaults.{$locale}", config('seo.meta.defaults.' . config('app.fallback_locale', 'az'), []));

        return [
            'title'       => $title !== '' ? $title : (string) (($defaults['title'] ?? '') ?: config('app.name', 'Gopanel')),
            'description' => $description !== '' ? $description : trim((string) ($defaults['description'] ?? '')),
        ];
    }

    private function canonical(string $locale, string $path): string
    {
        return url('/' . $locale . ($path === '' ? '' : '/' . trim($path, '/')));
    }

    private function label(string $locale, string $key): string
    {
        return (string) (config("seo.markdown.labels.{$locale}.{$key}")
            ?? config("seo.markdown.labels.default.{$key}", $key));
    }

    private function plain(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function finish(array $lines): string
    {
        return rtrim(implode("\n", $lines)) . "\n";
    }

    private function fullEnabled(): bool
    {
        return (bool) config('seo.markdown.llms_full.enabled', true);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function cached(string $name, array $filters, Closure $render): string
    {
        $filters['fp'] = $this->content->fingerprint();

        return (string) CacheService::remember(
            CacheKey::seo($name, $filters),
            $render,
            max(60, (int) config('seo.markdown.cache_ttl', 3600)),
        );
    }

    /**
     * `/{dil}`, `/{dil}/{slug}` → `[dil, yol]`; Markdown ünvanı olmayan yol üçün `null`.
     *
     * `markdown_url()` helper-i və middleware bunu işlədir.
     *
     * @return array{0: string, 1: string}|null
     */
    public function splitPath(string $path): ?array
    {
        $path = trim($path, '/');

        if ($path === '' || preg_match('/\.(md|txt|xml|opml|json)$/i', $path)) {
            return null;
        }

        $segments = explode('/', $path);
        $locale   = (string) array_shift($segments);

        if (!preg_match('/^[a-z]{2}$/', $locale)) {
            return null;
        }

        return [$locale, implode('/', $segments)];
    }
}
