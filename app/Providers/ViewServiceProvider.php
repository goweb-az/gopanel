<?php

// app/Providers/ViewServiceProvider.php
namespace App\Providers;

use App\Helpers\Gopanel\GoPanelSidebar;
use App\Models\Geography\Language;
use App\Models\Settings\SiteSetting;
use App\Models\Site\Menu;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

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


            $siteSettings = SiteSetting::latest()->first();
            $view->with('siteSettings', $siteSettings);
            if ($siteSettings?->site_status == 0)
                return;
            $view->with('siteSettings', $siteSettings);
        });
    }

    private function sendViewMetaData($menus)
    {
        // URL-dəki ikinci seqmenti al (sayfa adı)
        $currentSegment = request()->segment(2);

        // Segment ilə uyğun gələn menyu məlumatlarını tap
        $menu = $menus->firstWhere('route', $currentSegment);

        // Əgər segmentlə uyğun gələn menyu tapılmazsa, varsayılan olaraq "home" menyusunu al
        if (!$menu) {
            $menu = Menu::where('route', 'home')->first();
        }
        // Menu məlumatları tapılarsa, meta məlumatlarını al
        if ($menu) {
            // Menu üçün meta məlumatlarını al
            $metaData = $menu->meta()->first();

            // Əgər meta məlumatları yoxdursa, varsayılan meta məlumatlarını istifadə et
            $metaTitle = $metaData->title ?? 'Qrgate.az';
            $metaDescription = $metaData->description ?? 'Qragte - Vaxta Nəzarət et!!!';
            $metaKeywords = $metaData->keywords ?? 'qrkod,giris cixis,';
            $metaImage = $menu->getMetaImage() ?? null;

            // Meta məlumatlarını view'a (görünümə) göndər
            view()->share('meta_title', $metaTitle);
            view()->share('meta_description', $metaDescription);
            view()->share('meta_keywords', $metaKeywords);
            view()->share('meta_image', $metaImage);
        }
    }
}
