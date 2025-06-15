<?php

// app/Providers/ViewServiceProvider.php
namespace App\Providers;

use App\Helpers\Common\CrossAuth;
use App\Helpers\Gopanel\GoPanelSidebar;
use App\Helpers\Panel\PanelSidebar;
use App\Models\Geography\Language;
use App\Models\Site\CalculatorSettingFront;
use App\Models\Site\LegalPage;
use App\Models\Site\Menu;
use App\Models\Site\SiteSetting;
use App\Models\Site\SocialNetwork;
use App\Services\Payment\PlanCalculatorService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
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
        $this->generatePanelSidebar();
        $this->generateGopanelSidebar();
        $this->shareLanguages();
        $this->shareSiteContent();
        $this->calculatingData();
    }

    /**
     * @throws \Exception
     */
    public function generatePanelSidebar(): void
    {
        view()->composer('panel.blocks.sidebar', function () {
            $adminSidebarMenu = PanelSidebar::getInstance();
            $adminSidebarMenu->addItems(config('panel.sidebar_menu_list'));
            view()->share('sidebarItems', PanelSidebar::getInstance()->getItems());
        });
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
                return Language::where('is_active', true)->get();
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
            //Sayt statusu aciqdirsa kod davam edecek
            $menus = Cache::remember("site_menus", now()->addDay(), function () {
                return Menu::where('status', true)->with("translations", 'meta')->get();
            });
            $social_network = Cache::remember("social_network", now()->addDay(), function () {
                return SocialNetwork::where('is_active', true)->get();
            });
            $legal_pages = Cache::remember("legal_pages", now()->addDay(), function () {
                return LegalPage::where('is_active', true)->with("translations", 'meta')->get();
            });
            $view->with('siteSettings', $siteSettings);
            $view->with('menus', $menus);
            $view->with('social_network', $social_network);
            $view->with('legal_pages', $legal_pages);
            $view->with('panel_can_logged', CrossAuth::check());
            $this->sendViewMetaData($menus);
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




    private function calculatingData()
    {
        View::composer('site.blocks.home.calculator', function ($view) {
            $calculateFront = Cache::remember("calculateFront", now()->addDay(), function () {
                $calculator             = new PlanCalculatorService();
                $calculatorsetting      = CalculatorSettingFront::getCached();
                if (isset($calculatorsetting->id) && $calculatorsetting->is_active == 1) {
                    $gate_limit       = $calculatorsetting->min_gate;
                    $personal_limit   = $calculatorsetting->min_personal;
                } else {
                    $gate_limit       = 1;
                    $personal_limit   = 3;
                }
                $calculator->setCounts($gate_limit, $personal_limit);
                $calculator->setMonths(12);
                $calculate = $calculator->calculate()->get();
                return [
                    'total_price'           => "{$calculate->price} ₼ <span>" . __('title.calculator_edv') . "</span>",
                    'gate_range_personal'   => "{$gate_limit}-{$personal_limit} <span>" . __('title.calculator_piece') . "</span>",
                    'price_per_person'      => "{$calculate->price_per_person} ₼ <span>" . __('title.calculator_edv') . "</span>",
                    'anual_price'           => "{$calculate->duration_discounted_price} ₼ <span>" . __('title.calculator_edv') . "</span>",
                    'currency'              => '₼'
                ];
            });
            $view->with('calculateFront', $calculateFront);
        });
    }
}
