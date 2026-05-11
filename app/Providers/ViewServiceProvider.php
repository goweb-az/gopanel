<?php

// app/Providers/ViewServiceProvider.php

namespace App\Providers;

use App\Enums\Common\SocialIconTypeEnum;
use App\Models\Contact\ContactInfo;
use App\Models\Contact\Social;
use App\Models\Navigation\Menu;
use App\Models\Seo\SeoAnalytics;
use App\Models\Settings\SiteSetting;
use App\Services\Site\Seo\AlternatesService;
use App\Services\Site\Seo\MetaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->markSiteViews();
        $this->shareSiteContent();
        $this->shareSiteMetaData();
        $this->shareAlternatesLinks();
    }

    private function markSiteViews(): void
    {
        View::composer('gopanel.pages.site.*', function ($view) {
            $view->with('isSiteView', true);
        });
    }

    private function shareSiteContent()
    {
        View::composer('site.*', function ($view) {
            $siteSettings = SiteSetting::getCached();
            $seoAnalytics = SeoAnalytics::getCached();
            $locale = app(\App\Services\Locale\LocaleManager::class);

            $view->with('siteSettings', $siteSettings);
            $view->with('seoAnalytics', $seoAnalytics);
            $view->with('contactInfo', ContactInfo::getCached());
            $view->with('socials', Social::getCached());
            $view->with('company_name', $siteSettings->company_name ?? config('app.name', 'Gopanel'));
            $view->with('menus', Menu::getSiteMenu());
            $view->with('SocialIconTypeEnum', SocialIconTypeEnum::class);
            $view->with('languages', $locale->all());
            $view->with('currentLocale', $locale->current());
        });
    }

    private function shareSiteMetaData(): void
    {
        View::composer('site.layouts.head', function ($view) {
            // Artıq controller-dən share olunubsa (Blog single, Service single vs.), override etmə
            $shared = View::getShared();
            if (! empty($shared['meta_title'])) {
                return;
            }

            /** @var Request $request */
            $request = app(Request::class);

            /** @var MetaService $metaService */
            $metaService = app(MetaService::class);

            $menus = Menu::getSiteMenu();
            $meta = $metaService->compose($menus, $request);
            MetaService::share($meta);
        });
    }

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
}
