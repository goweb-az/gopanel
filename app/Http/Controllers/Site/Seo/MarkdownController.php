<?php

namespace App\Http\Controllers\Site\Seo;

use App\Http\Controllers\Site\SiteController;
use App\Services\Seo\Markdown\MarkdownPageService;
use App\Services\Seo\PublicContentService;
use App\Services\Site\Seo\SlugAliasResolver;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * Publik səhifələrin Markdown variantı: canonical URL + `.md`.
 * Siyahı `/markdown-index.md`-dədir. Sənəd: docs/markdown-pages.md
 *
 * Route-lar dil prefiksli qrupdan KƏNARDADIR (və ondan ƏVVƏL qeyd olunub) -
 * əks halda `/{dil}/{slug}` catch-all-ı `bloqlar.md`-ni slug kimi oxuyub 404
 * verərdi. `language` middleware-i tələb olunmur - dil yolun özündən gəlir.
 * `site.redirects` və `track.analytics` qoşulmur: `.md` sorğusu HTML səhifənin
 * ziyarəti kimi sayılmamalıdır.
 */

class MarkdownController extends SiteController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request, MarkdownPageService $markdown): Response
    {
        return $this->markdownResponse($markdown->index(), app()->getLocale());
    }

    /**
     * `/{dil}.md` və `/{dil}/{yol}.md`.
     */
    public function page(MarkdownPageService $markdown, string $locale, string $path = ''): Response
    {
        if (!app(PublicContentService::class)->isActiveLocale($locale)) {
            abort(404);
        }

        // Route `.md` ilə bitən yolu tutur - servisə təmiz yol verilir
        $path = trim(preg_replace('/\.md$/', '', $path) ?? $path, '/');

        app()->setLocale($locale);

        $content = $markdown->page($locale, $path);

        if ($content === null) {
            return $this->redirectWrongLanguageSlug($locale, $path);
        }

        return $this->markdownResponse($content, $locale, url($locale . ($path === '' ? '' : '/' . $path)));
    }

    /**
     * Yanlış dildəki slug (`/en/bloqlar.md`) 404 vermir: HTML tərəfin
     * `SlugAliasResolver`-i düzgün yolu tapırsa `.md` variantına 301 verilir.
     */
    private function redirectWrongLanguageSlug(string $locale, string $path): Response
    {
        $segments = array_values(array_filter(explode('/', $path), static fn ($s): bool => $s !== ''));

        if ($segments !== []) {
            $corrected = app(SlugAliasResolver::class)->correctPath($locale, $segments);

            if ($corrected !== null && trim($corrected, '/') !== $locale . '/' . $path) {
                return redirect()->to(url($corrected) . '.md', 301);
            }
        }

        abort(404);
    }

    private function markdownResponse(string $content, string $locale, ?string $canonical = null): Response
    {
        $response = response($content, 200)
            ->header('Content-Type', 'text/markdown; charset=UTF-8')
            // Fayl brauzerdə açılsın, endirilməsin
            ->header('Content-Disposition', 'inline')
            // Eyni məzmun HTML səhifədə də var - indeks yalnız HTML variantına düşsün
            ->header('X-Robots-Tag', 'noindex, follow')
            ->header('Content-Language', $locale);

        if ($canonical !== null) {
            $response->header('Link', '<' . $canonical . '>; rel="canonical"');
        }

        return $response;
    }
}
