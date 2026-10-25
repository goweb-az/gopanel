<?php

// app/Providers/ViewServiceProvider.php
namespace App\Providers;

use App\Enums\Common\SocialIconTypeEnum;
use App\Helpers\Gopanel\GoPanelSidebar;
use App\Models\Contact\ContactInfo;
use App\Models\Contact\Social;
use App\Models\Geography\Language;
use App\Models\Navigation\Menu;
use App\Models\Seo\SeoAnalytics;
use App\Models\Settings\SiteSetting;
use App\Services\Site\Seo\AlternatesService;
use App\Services\Site\Seo\MetaService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->generateGopanelSidebar();
        $this->shareLanguages();
        $this->shareSiteContent();
        $this->shareGopanelContent();
        $this->shareSiteMetaData();
        $this->shareAlternatesLinks();
        $this->shareSiteSeoExtras();
    }



    /**
     * @throws \Exception
     */
    public function generateGopanelSidebar(): void
    {

        view()->composer('gopanel.blocks.sidebar', function () {
            $adminSidebarMenu = GoPanelSidebar::getInstance();
            $adminSidebarMenu->addItems(config('gopanel.sidebar_menu_list'));
            view()->share('sidebarItems', GoPanelSidebar::getInstance()->getItems());
        });

        View::composer('gopanel.pages.site.*', function ($view) {
            $view->with('isSiteView', true);
        });
    }

    private function shareLanguages()
    {
        View::composer('*', function ($view) {
            if (!Schema::hasTable('languages')) {
                $view->with('languages', collect());
                $view->with('currentLocale', config('app.locale', 'az'));
                return;
            }
            $languages = Cache::remember("languages", now()->addDay(), function () {
                return Language::where('is_active', true)->orderBy("sort_order", "asc")->get();
            });
            $currentLocale = app()->getLocale();
            $view->with('languages', $languages);
            $view->with('currentLocale', $currentLocale);
        });
    }


    private function shareSiteContent()
    {
        View::composer('site.*', function ($view) {
            $siteSettings   = SiteSetting::getCached();
            $seoAnalytics   = SeoAnalytics::getCached();

            $view->with('siteSettings', $siteSettings);
            $view->with('seoAnalytics', $seoAnalytics);
            $view->with('contactInfo', ContactInfo::getCached());
            $view->with('socials', Social::getCached());
            $view->with('company_name', $siteSettings->company_name ?? config('app.name', 'Gopanel'));
            $view->with('menus', Menu::getSiteMenu());
            $view->with('SocialIconTypeEnum', SocialIconTypeEnum::class);
        });
    }


    private function shareGopanelContent()
    {
        View::composer('gopanel.*', function ($view) {
            $settings = SiteSetting::latest()->first();
            $view->with('settings', $settings);
        });
    }


    /**
     * Site üçün meta data (title, description, keywords, image) paylaşır
     */
    private function shareSiteMetaData(): void
    {
        View::composer('site.layouts.head', function ($view) {
            // Artıq controller-dən share olunubsa (Blog single, Service single vs.), override etmə
            $shared = View::getShared();
            if (!empty($shared['meta_title'])) {
                return;
            }

            /** @var Request $request */
            $request = app(Request::class);

            /** @var MetaService $metaService */
            $metaService = app(MetaService::class);

            // composeForRequest: menyu (ana səhifədə `home` menyusu) → SiteSetting → config/seo/meta.php
            $meta = $metaService->composeForRequest($request);
            MetaService::share($meta);
            $view->with([
                'site_title'       => $meta['site_title'],
                'meta_title'       => $meta['title'],
                'meta_description' => $meta['description'],
                'meta_keywords'    => $meta['keywords'],
                'meta_image'       => $meta['image'],
            ]);
        });
    }


    /**
     * Alternates və hreflang linkləri site layout-a enjekte edir
     */
    private function shareAlternatesLinks(): void
    {
        View::composer('site.layouts.head', function ($view) {
            /** @var Request $request */
            $request = request();

            /** @var AlternatesService $alternateService */
            $alternateService = app(AlternatesService::class);
            $data = $alternateService->compose($request);
            $view->with($data);
        });
    }


    /**
     * SEO əlavələri - qərar BURADA verilir, blade yalnız hazır dəyəri çap edir:
     *
     *   page_robots        controller `setPageRobots()` çağırmayıbsa config default-u
     *   markdown_url       səhifənin `.md` variantı yoxdursa `null` - link çap olunmur
     *   rss_feed_url       RSS route-u aktiv dillər üzrə qurulur; yoxdursa `null`
     *   og_locale(_alternates), og_site_name   meta.blade.php üçün
     *   schema_graph       controller qraf verməyibsə baza qovşaqları (SchemaService)
     *   language_urls      header/footer dil keçidləri - slug da tərcümə olunur
     *
     * Head kompozerləri yalnız `<head>` render olunanda işləyir.
     */
    private function shareSiteSeoExtras(): void
    {
        View::composer('site.layouts.head', function ($view) {
            try {
                $locale   = app()->getLocale();
                $rssRoute = "site.{$locale}.rss.single";
                $ogMap    = (array) config('seo.meta.og_locales', []);
                $codes    = Schema::hasTable('languages') ? Language::getCachedAll()->pluck('code')->all() : [];

                $view->with([
                    'page_robots'          => View::shared('page_robots') ?: config('seo.meta.robots', 'index, follow'),
                    'markdown_url'         => markdown_url(),
                    'rss_feed_url'         => \Illuminate\Support\Facades\Route::has($rssRoute) ? route($rssRoute) : null,
                    'og_locale'            => $ogMap[$locale] ?? $locale,
                    'og_locale_alternates' => array_values(array_map(
                        fn ($code) => $ogMap[$code] ?? $code,
                        array_filter($codes, fn ($code) => $code !== $locale)
                    )),
                    'og_site_name'         => config('seo.schema.organization.name') ?: config('app.name'),
                ]);

                if (empty(View::shared('schema_markup_source'))) {
                    $view->with([
                        'schema_markup_source' => 'site.schema-markups.render',
                        'schema_graph'         => app(\App\Services\Seo\SchemaService::class)->base(),
                    ]);
                }
            } catch (\Throwable $e) {
                report($e);
                $view->with('page_robots', config('seo.meta.robots', 'index, follow'));
            }
        });

        View::composer(['site.layouts.header', 'site.layouts.footer'], function ($view) {
            $request = request();
            $urls    = $request->attributes->get('seo.language_urls');

            if (!is_array($urls)) {
                $urls = [];

                try {
                    $switcher = app(\App\Services\Site\Seo\LanguageSwitcherService::class);

                    foreach (Schema::hasTable('languages') ? Language::getCachedAll() : [] as $language) {
                        $urls[$language->code] = $switcher->urlFor($language);
                    }
                } catch (\Throwable $e) {
                    report($e);
                }

                $request->attributes->set('seo.language_urls', $urls);
            }

            $view->with('language_urls', $urls);
        });
    }

}
