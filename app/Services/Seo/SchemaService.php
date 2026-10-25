<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Models\Contact\ContactInfo;
use App\Models\Contact\Social;
use App\Models\Settings\SiteSetting;
use App\Support\Url\CdnUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * JSON-LD (schema.org) qovşaqlarının qurulması.
 *
 * NİYƏ: blade-də əl ilə JSON yazmaq kövrəkdir - başlıqdakı dırnaq, sətir
 * keçidi və ya `</script>` markup-ı sındırırdı (köhnə `schema-markups/*.blade.php`
 * mətni birbaşa `"{{ }}"` içinə qoyurdu). Burada massiv qurulur,
 * `site/schema-markups/render.blade.php` isə `json_encode` ilə çap edir:
 * nəticə həmişə etibarlı JSON olur.
 *
 * Qovşaqlar bir-birinə `@id` ilə bağlanır (`{home}#organization`,
 * `{home}#website`) - axtarış sistemləri bütün səhifələri eyni şirkətə aid edir.
 * Şəhər/ölkə/təşkilat tipi `config/seo/schema.php`-dədir, kodda hardcode edilmir.
 *
 * İstifadə (controller): `$this->setSchemaGraph([$this->schema()->blogPosting($blog)])`.
 * Baza qovşaqları (`base()`) avtomatik əlavə olunur. Data yetərli olmayanda
 * metodlar `null` qaytarır və qovşaq qrafa düşmür.
 */
class SchemaService
{
    private mixed $settings = null;
    private mixed $contact  = null;
    private bool $loaded    = false;

    /**
     * Bütün səhifələrdə olan baza qovşaqları (`config/seo/schema.php` → base_nodes).
     *
     * @return array<int, array<string, mixed>>
     */
    public function base(): array
    {
        $nodes = [];

        foreach ((array) config('seo.schema.base_nodes', ['organization', 'website']) as $name) {
            $node = match ($name) {
                'organization' => $this->organization(),
                'website'      => $this->webSite(),
                default        => null,
            };

            if ($node !== null) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /** Şirkət. */
    public function organization(): array
    {
        $home    = $this->homeUrl();
        $config  = (array) config('seo.schema.organization', []);
        $contact = $this->contact();

        $node = [
            '@type' => $config['type'] ?? 'Organization',
            '@id'   => $home . '#organization',
            'name'  => $this->siteName(),
            'url'   => $home,
        ];

        if ($logo = $this->logoUrl()) {
            $node['logo'] = ['@type' => 'ImageObject', 'url' => $logo];
        }

        $address = array_filter([
            'streetAddress'   => $contact ? $this->plain($this->translatedContact('adress')) : null,
            'addressLocality' => $config['address_locality'] ?? null,
            'addressCountry'  => $config['address_country'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if ($address !== []) {
            $node['address'] = ['@type' => 'PostalAddress'] + $address;
        }

        $contactPoint = array_filter([
            'telephone' => $contact?->phone ?: $contact?->mobile,
            'email'     => $contact?->info_email ?: $contact?->support_email,
        ], fn ($value) => !empty($value));

        if ($contactPoint !== []) {
            $node['contactPoint'] = array_merge([
                '@type'             => 'ContactPoint',
                'contactType'       => $config['contact_type'] ?? 'customer support',
                'availableLanguage' => app(PublicContentService::class)->locales(),
            ], $contactPoint);
        }

        $sameAs = $this->socialUrls();

        if ($sameAs !== []) {
            $node['sameAs'] = $sameAs;
        }

        return $node;
    }

    /** Sayt özü. */
    public function webSite(): array
    {
        $home = $this->homeUrl();

        return [
            '@type'      => 'WebSite',
            '@id'        => $home . '#website',
            'name'       => $this->siteName(),
            'url'        => $home,
            'inLanguage' => app()->getLocale(),
            'publisher'  => ['@id' => $home . '#organization'],
        ];
    }

    /**
     * Naviqasiya yolu. İki addımdan az olsa `null` (tək addımlı yol mənasızdır).
     *
     * @param array<int, array{name: ?string, url: ?string}> $trail
     */
    public function breadcrumb(array $trail): ?array
    {
        $items    = [];
        $position = 1;

        foreach ($trail as $step) {
            $name = $this->plain($step['name'] ?? null);

            if ($name === null) {
                continue;
            }

            $item = ['@type' => 'ListItem', 'position' => $position++, 'name' => $name];

            if (!empty($step['url'])) {
                $item['item'] = $step['url'];
            }

            $items[] = $item;
        }

        return count($items) < 2 ? null : ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    /** Bloq yazısı. */
    public function blogPosting(Model $item, ?string $url = null): array
    {
        return $this->article($item, 'BlogPosting', $url);
    }

    /** Xəbər. */
    public function newsArticle(Model $item, ?string $url = null): array
    {
        return $this->article($item, 'NewsArticle', $url);
    }

    /**
     * Siyahı səhifəsi (bloq, xidmətlər...).
     *
     * @param iterable<int, Model> $items
     * @param callable(Model): ?string $urlResolver
     */
    public function itemList(iterable $items, string $name, callable $urlResolver, ?int $limit = null): ?array
    {
        $limit    ??= (int) config('seo.schema.item_list_limit', 50);
        $elements = [];
        $position = 1;

        foreach ($items as $item) {
            if ($position > $limit) {
                break;
            }

            $url = $urlResolver($item);

            if (!$url) {
                continue;
            }

            $elements[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $this->plain($item->title ?? $item->name ?? '') ?? '',
                'url'      => $url,
            ];
        }

        if ($elements === []) {
            return null;
        }

        return array_filter([
            '@type'           => 'ItemList',
            'name'            => $this->plain($name),
            'numberOfItems'   => count($elements),
            'itemListElement' => $elements,
        ], fn ($value) => $value !== null);
    }

    /**
     * Tez-tez verilən suallar. Sahə adları: `question`/`answer` və ya `title`/`description`.
     *
     * @param iterable<int, mixed> $faqs
     */
    public function faqPage(iterable $faqs): ?array
    {
        $entities = [];

        foreach ($faqs as $faq) {
            $question = $this->plain(data_get($faq, 'question') ?? data_get($faq, 'title'));
            $answer   = $this->plain(data_get($faq, 'answer') ?? data_get($faq, 'description'));

            if ($question === null || $answer === null) {
                continue;
            }

            $entities[] = [
                '@type'          => 'Question',
                'name'           => $question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
            ];
        }

        return $entities === [] ? null : ['@type' => 'FAQPage', 'mainEntity' => $entities];
    }

    /**
     * Sadə səhifə (`WebPage`, `ContactPage`, `AboutPage`, `CollectionPage`...).
     */
    public function webPage(string $type, ?string $name, ?string $description = null, ?string $image = null, ?string $url = null): array
    {
        return array_filter([
            '@type'       => $type,
            'name'        => $this->plain($name),
            'description' => $this->plain($description),
            'url'         => $url ?: url()->current(),
            'image'       => $image,
            'inLanguage'  => app()->getLocale(),
            'isPartOf'    => ['@id' => $this->homeUrl() . '#website'],
        ], fn ($value) => $value !== null && $value !== '');
    }

    /** Bloq/xəbər üçün ortaq qəlib. */
    private function article(Model $item, string $type, ?string $url = null): array
    {
        $home = $this->homeUrl();
        $url  = $url ?: url()->current();
        $raw  = $item->getAttributes();

        $node = array_filter([
            '@type'         => $type,
            'headline'      => Str::limit((string) $this->plain($item->title ?? $item->name ?? ''), 110, ''),
            'description'   => $this->summary($item),
            'url'           => $url,
            'image'         => $this->imageUrl($item),
            'datePublished' => $this->date($raw['date_time'] ?? null) ?? $this->date($item->created_at ?? null),
            'dateModified'  => $this->date($item->updated_at ?? $item->created_at ?? null),
            'inLanguage'    => app()->getLocale(),
        ], fn ($value) => $value !== null && $value !== '');

        $node['author']           = ['@id' => $home . '#organization'];
        $node['publisher']        = ['@id' => $home . '#organization'];
        $node['mainEntityOfPage'] = ['@type' => 'WebPage', '@id' => $url];

        return $node;
    }

    /** Qeydin qısa təsviri - HTML təmizlənir, 300 simvola qədər kəsilir. */
    private function summary(Model $item): ?string
    {
        foreach (['short_description', 'description'] as $key) {
            $value = $this->plain($item->{$key} ?? null);

            if ($value !== null && mb_strlen($value) > 30) {
                return Str::limit($value, 300);
            }
        }

        return null;
    }

    /** Şəklin tam ünvanı: `image_url` accessor-u (HasFiles) → `image` sütunu. */
    private function imageUrl(Model $item): ?string
    {
        $url = $item->image_url ?? null;

        if (is_string($url) && $url !== '') {
            return CdnUrl::isAbsolute($url) ? $url : CdnUrl::url($url);
        }

        $path = $item->getAttributes()['image'] ?? null;

        return is_string($path) && trim($path) !== '' ? CdnUrl::url($path) : null;
    }

    /**
     * HTML və artıq boşluqlardan təmizlənmiş mətn. `ENT_HTML5` - `&#601;` (ə)
     * kimi rəqəmli kodlar da açılsın.
     */
    private function plain(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean   = trim(preg_replace('/\s+/u', ' ', strip_tags($decoded)) ?? '');

        return $clean !== '' ? $clean : null;
    }

    private function date(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_object($value) && method_exists($value, 'toIso8601String')) {
            return $value->toIso8601String();
        }

        $time = strtotime((string) $value);

        return $time ? date('c', $time) : null;
    }

    private function homeUrl(): string
    {
        return url(app()->getLocale());
    }

    /** Ad: config → SiteSetting.company_name → app.name. */
    private function siteName(): string
    {
        $name = config('seo.schema.organization.name')
            ?: ($this->settings()?->company_name ?? null)
            ?: config('app.name', 'Gopanel');

        return (string) $name;
    }

    private function logoUrl(): ?string
    {
        $settings = $this->settings();
        $url      = $settings?->logo_light_url ?: $settings?->logo_dark_url;

        if (is_string($url) && $url !== '') {
            return CdnUrl::isAbsolute($url) ? $url : CdnUrl::url($url);
        }

        $fallback = config('seo.meta.logo');

        return $fallback ? CdnUrl::url((string) $fallback) : null;
    }

    /** @return array<int, string> */
    private function socialUrls(): array
    {
        try {
            return (Social::getCached() ?? collect())->pluck('url')->filter()->values()->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function translatedContact(string $key): ?string
    {
        $contact = $this->contact();

        return $contact ? app(PublicContentService::class)->translated($contact, $key, app()->getLocale()) : null;
    }

    private function settings(): mixed
    {
        $this->load();

        return $this->settings;
    }

    private function contact(): mixed
    {
        $this->load();

        return $this->contact;
    }

    /** Cədvəllər hələ yoxdursa (təzə quraşdırma) markup sadəcə qısa olur. */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        try {
            $this->settings = SiteSetting::getCached();
            $this->contact  = ContactInfo::getCached();
        } catch (Throwable) {
            $this->settings = null;
            $this->contact  = null;
        }
    }
}
