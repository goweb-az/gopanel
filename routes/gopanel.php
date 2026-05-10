<?php

use App\Http\Controllers\Gopanel\AboutUsController;
use App\Http\Controllers\Gopanel\Activity\ActivityLogController;
use App\Http\Controllers\Gopanel\Activity\FileLogController;
use App\Http\Controllers\Gopanel\Admins\AdminController;
use App\Http\Controllers\Gopanel\Admins\ProfileController;
use App\Http\Controllers\Gopanel\Admins\RoleController;
use App\Http\Controllers\Gopanel\AuthController;
use App\Http\Controllers\Gopanel\BlogController;
use App\Http\Controllers\Gopanel\CategoryController;
use App\Http\Controllers\Gopanel\Common\GeneralController;
use App\Http\Controllers\Gopanel\Contact\ContactInfoController;
use App\Http\Controllers\Gopanel\Contact\SocialController;
use App\Http\Controllers\Gopanel\DashboardController;
use App\Http\Controllers\Gopanel\DatatableController;
use App\Http\Controllers\Gopanel\ProductController;
use App\Http\Controllers\Gopanel\Seo\AnalyticsController;
use App\Http\Controllers\Gopanel\Seo\AnalyticsDetailController;
use App\Http\Controllers\Gopanel\Seo\LlmsTxtController;
use App\Http\Controllers\Gopanel\Seo\SeoAnalyticsController;
use App\Http\Controllers\Gopanel\Seo\SiteRedirectController;
use App\Http\Controllers\Gopanel\ServiceController;
use App\Http\Controllers\Gopanel\Settings\MenuController;
use App\Http\Controllers\Gopanel\Settings\SiteSettingsController;
use App\Http\Controllers\Gopanel\SliderController;
use App\Http\Controllers\Gopanel\System\UpdateController;
use App\Http\Controllers\Gopanel\Translations\LanguageController;
use App\Http\Controllers\Gopanel\Translations\TranslationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('test', [TestController::class, 'index'])->name('index');

// Auth Proccess
Route::prefix('auth')->name('auth.')->group(function () {
    // MIGRATED to Livewire SFC — handles GET render + POST authenticate inside the component
    Route::livewire('/login', 'gopanel.auth.login')->name('login');

    // Logout still posts to the controller (stateless, no Livewire needed)
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    // @deprecated — legacy AJAX login endpoint
    Route::post('login-legacy', [AuthController::class, 'attempt'])->name('login.proccess');
});

// Start Gopanel route group
Route::group(['middleware' => 'gopanel'], function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('index');

    // Livewire smoke test (TEMP — remove before production)
    Route::livewire('/_lw-probe', 'test-probe')->name('_lw.probe');
    Route::livewire('/_dt-probe', 'dt-probe')->name('_dt.probe');
    // Datatable
    Route::get('datatable/{table}', [DatatableController::class, 'handle'])->name('datatable.source');

    // General default routes
    Route::prefix('general')->name('general.')->group(function () {
        Route::get('/clear/cache', [GeneralController::class, 'clearCache'])->name('clear.cache');
        Route::get('/get/route', [GeneralController::class, 'route'])->name('get.route');
        Route::get('/icon-picker/list', [GeneralController::class, 'iconPickerList'])->name('icon-picker.list');
        Route::post('/status/change', [GeneralController::class, 'statusChange'])->name('status.change');
        Route::post('/sortable', [GeneralController::class, 'sortable'])->name('sortable');
        Route::post('/add', [GeneralController::class, 'add'])->name('add');
        Route::post('/delete/{id?}', [GeneralController::class, 'delete'])->name('delete');
        Route::post('/archive/{id?}', [GeneralController::class, 'archive'])->name('archive');
        Route::post('/edit/{id?}', [GeneralController::class, 'edit'])->name('edit');
        Route::post('/editable/{id?}', [GeneralController::class, 'editable'])->name('editable');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        // Site Settings
        // MIGRATED to Livewire SFC — resources/views/livewire/gopanel/site-settings/index.blade.php
        Route::prefix('site-settings')->name('site-settings.')->group(function () {
            Route::livewire('/', 'gopanel.site-settings.index')->name('index');
        });

        // @deprecated — legacy AJAX endpoint (also retains SEO meta save flow)
        Route::prefix('site-settings-legacy')->name('site-settings.legacy.')->group(function () {
            Route::get('/{item?}', [SiteSettingsController::class, 'index'])->name('index');
            Route::post('/save/{item?}', [SiteSettingsController::class, 'save'])->name('save.form');
        });

        // Route::prefix('media')->name("media.")->group(function () {
        //     Route::get('/{type}/{id}', [MediaController::class, 'index'])->name('index');
        //     Route::post('/upload/{type}/{id}', [MediaController::class, 'upload'])->name('upload');
        //     Route::delete('/delete/{media}', [MediaController::class, 'delete'])->name('delete');
        // });

        // MIGRATED to Livewire SFC — Menu
        Route::prefix('menu')->name('menu.')->group(function () {
            Route::livewire('/',                 'gopanel.menu.index')->name('index');
            Route::livewire('/create',           'gopanel.menu.create')->name('create');
            Route::livewire('/{menu}/edit',      'gopanel.menu.edit')->name('edit');
        });

        // @deprecated — legacy AJAX endpoints
        Route::prefix('menu-legacy')->name('menu.legacy.')->group(function () {
            Route::get('/store/{item?}', [MenuController::class, 'store'])->name('store');
            Route::post('/save/{item?}', [MenuController::class, 'save'])->name('save');
        });

        // MIGRATED to Livewire SFC — resources/views/livewire/gopanel/language/index.blade.php
        Route::prefix('languages')->name('languages.')->group(function () {
            Route::livewire('/', 'gopanel.language.index')->name('index');
        });

        // @deprecated — kept for backwards compatibility (old AJAX endpoints only)
        Route::prefix('languages-legacy')->name('languages.legacy.')->group(function () {
            Route::get('/get/form/{item?}', [LanguageController::class, 'getForm'])->name('get.form');
            Route::post('/save/{item?}', [LanguageController::class, 'save'])->name('save');
            Route::post('/toggle-default', [LanguageController::class, 'toggleDefault'])->name('toggle.default');
        });

        // MIGRATED to Livewire SFC — Translations
        Route::prefix('translations')->name('translations.')->group(function () {
            Route::livewire('/', 'gopanel.translation.index')->name('index');
        });

        // @deprecated — legacy AJAX endpoints
        Route::prefix('translations-legacy')->name('translations.legacy.')->group(function () {
            Route::get('/get/form/{item?}', [TranslationController::class, 'getForm'])->name('get.form');
            Route::post('/save/form/{item?}', [TranslationController::class, 'save'])->name('save.form');
        });
    });

    Route::prefix('contact')->name('contact.')->group(function () {
        // MIGRATED to Livewire SFC — resources/views/livewire/gopanel/contact-info/index.blade.php
        Route::prefix('contact-info')->name('contact-info.')->group(function () {
            Route::livewire('/', 'gopanel.contact-info.index')->name('index');
        });

        // @deprecated — legacy AJAX endpoint
        Route::prefix('contact-info-legacy')->name('contact-info.legacy.')->group(function () {
            Route::post('/save/{item?}', [ContactInfoController::class, 'save'])->name('save.form');
        });

        // MIGRATED to Livewire SFC — resources/views/livewire/gopanel/social/index.blade.php
        Route::prefix('socials')->name('socials.')->group(function () {
            Route::livewire('/', 'gopanel.social.index')->name('index');
        });

        // @deprecated — legacy AJAX endpoints
        Route::prefix('socials-legacy')->name('socials.legacy.')->group(function () {
            Route::get('/get/form/{item?}', [SocialController::class, 'getForm'])->name('get.form');
            Route::post('/save/{item?}', [SocialController::class, 'save'])->name('save');
        });
    });

    // MIGRATED to Livewire SFC — SEO modules
    Route::prefix('seo')->name('seo.')->group(function () {
        Route::prefix('site-redirects')->name('site-redirects.')->group(function () {
            Route::livewire('/', 'gopanel.site-redirect.index')->name('index');
        });

        Route::prefix('seo-analytics')->name('seo-analytics.')->group(function () {
            Route::livewire('/', 'gopanel.seo-analytics.index')->name('index');
        });

        Route::prefix('llms-txt')->name('llms-txt.')->group(function () {
            Route::livewire('/', 'gopanel.llms-txt.index')->name('index');
        });
    });

    // @deprecated — legacy AJAX endpoints
    Route::prefix('seo-legacy')->name('seo.legacy.')->group(function () {
        Route::prefix('site-redirects')->name('site-redirects.')->group(function () {
            Route::get('/get/form/{item?}', [SiteRedirectController::class, 'getForm'])->name('get.form');
            Route::post('/save/{item?}', [SiteRedirectController::class, 'save'])->name('save');
        });

        Route::prefix('seo-analytics')->name('seo-analytics.')->group(function () {
            Route::post('/save/{item?}', [SeoAnalyticsController::class, 'save'])->name('save.form');
        });

        Route::prefix('llms-txt')->name('llms-txt.')->group(function () {
            Route::post('/save/{item?}', [LlmsTxtController::class, 'save'])->name('save.form');
        });
    });

    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/get/top-hits', [AnalyticsController::class, 'getTopHits'])->name('get.top.hits');
        Route::get('/get/countries-map', [AnalyticsController::class, 'getCountriesMap'])->name('get.countries.map');
        Route::get('/get/cities-chart', [AnalyticsController::class, 'getCitiesChart'])->name('cities.chart');
        Route::get('/get/languages-chart', [AnalyticsController::class, 'getLanguagesChart'])->name('get.languages');
        Route::get('/get/os-chart', [AnalyticsController::class, 'getOperatingSystemsChart'])->name('os.chart');
        // Select2 AJAX search
        Route::get('/api/countries', [AnalyticsController::class, 'searchCountries'])->name('api.countries');
        Route::get('/api/cities', [AnalyticsController::class, 'searchCities'])->name('api.cities');
        Route::prefix('detail')->name('detail.')->group(function () {
            Route::get('/devices', [AnalyticsDetailController::class, 'devices'])->name('devices');
            Route::get('/operating-systems', [AnalyticsDetailController::class, 'operating_systems'])->name('operating.systems');
            Route::get('/browsers', [AnalyticsDetailController::class, 'browsers'])->name('browsers');
            Route::get('/countries', [AnalyticsDetailController::class, 'countries'])->name('countries');
            Route::get('/cities', [AnalyticsDetailController::class, 'cities'])->name('cities');
            Route::get('/languages', [AnalyticsDetailController::class, 'languages'])->name('languages');
            Route::get('/clicks', [AnalyticsDetailController::class, 'clicks'])->name('clicks');
            Route::get('/links', [AnalyticsDetailController::class, 'links'])->name('links');
            Route::get('/utm/parameters', [AnalyticsDetailController::class, 'utm_parameters'])->name('utm.parameters');
            Route::get('/ad-platforms', [AnalyticsDetailController::class, 'ad_platforms'])->name('ad.platforms');
            Route::get('/ad-platform-data', [AnalyticsDetailController::class, 'ad_platform_data'])->name('ad.platform.data');
        });
    });

    // MIGRATED to Livewire SFC — Admins + Roles
    Route::prefix('admins')->name('admins.')->group(function () {
        Route::livewire('/',                'gopanel.admin.index')->name('index');
        Route::livewire('/create',          'gopanel.admin.create')->name('create');
        Route::livewire('/{admin}/edit',    'gopanel.admin.edit')->name('edit');

        Route::prefix('roles')->name('roles.')->group(function () {
            Route::livewire('/',              'gopanel.role.index')->name('index');
            Route::livewire('/create',        'gopanel.role.create')->name('create');
            Route::livewire('/{role}/edit',   'gopanel.role.edit')->name('edit');
        });
    });

    // @deprecated — legacy AJAX endpoints
    Route::prefix('admins-legacy')->name('admins.legacy.')->group(function () {
        Route::get('/get/form/{item?}', [AdminController::class, 'getForm'])->name('get.form');
        Route::post('/save/{item?}', [AdminController::class, 'save'])->name('save');

        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/store/{item?}', [RoleController::class, 'store'])->name('store');
            Route::post('/save/{item?}', [RoleController::class, 'save'])->name('save');
        });
    });

    // MIGRATED to Livewire SFC — Profile + Change Password
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::livewire('/',                'gopanel.profile.index')->name('index');
        Route::livewire('/change-password', 'gopanel.profile.change-password')->name('change-password.index');
    });

    // @deprecated — legacy AJAX endpoints
    Route::prefix('profile-legacy')->name('profile.legacy.')->group(function () {
        Route::post('/update', [ProfileController::class, 'update'])->name('update');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('change-password');
    });

    // MIGRATED to Livewire SFC — Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::livewire('/', 'gopanel.category.index')->name('index');
    });

    // @deprecated — legacy AJAX endpoints
    Route::prefix('categories-legacy')->name('categories.legacy.')->group(function () {
        Route::get('/get/form/{item?}', [CategoryController::class, 'getForm'])->name('get.form');
        Route::post('/save/{item?}', [CategoryController::class, 'save'])->name('save');
        Route::post('/move', [CategoryController::class, 'moveCategory'])->name('move');
    });

    // MIGRATED to Livewire SFC — resources/views/livewire/gopanel/blog/*
    Route::prefix('blog')->name('blog.')->group(function () {
        Route::livewire('/',                'gopanel.blog.index')->name('index');
        Route::livewire('/create',          'gopanel.blog.create')->name('create');
        Route::livewire('/{blog}/edit',     'gopanel.blog.edit')->name('edit');
    });

    // @deprecated — legacy AJAX endpoints (retains SEO meta save flow)
    Route::prefix('blog-legacy')->name('blog.legacy.')->group(function () {
        Route::get('/store/{item?}', [BlogController::class, 'store'])->name('store');
        Route::post('/save/{item?}', [BlogController::class, 'save'])->name('save');
    });

    // MIGRATED to Livewire SFC — resources/views/livewire/gopanel/about-us/index.blade.php
    Route::prefix('about-us')->name('about-us.')->group(function () {
        Route::livewire('/', 'gopanel.about-us.index')->name('index');
    });

    // @deprecated — legacy AJAX endpoint (retains SEO meta save flow)
    Route::prefix('about-us-legacy')->name('about-us.legacy.')->group(function () {
        Route::post('/save/{item?}', [AboutUsController::class, 'save'])->name('save');
    });

    // MIGRATED to Livewire SFC — resources/views/livewire/gopanel/service/index.blade.php
    Route::prefix('services')->name('services.')->group(function () {
        Route::livewire('/', 'gopanel.service.index')->name('index');
    });

    // @deprecated — legacy AJAX endpoints (also retains SEO meta save flow)
    Route::prefix('services-legacy')->name('services.legacy.')->group(function () {
        Route::get('/get/form/{item?}', [ServiceController::class, 'getForm'])->name('get.form');
        Route::post('/save/{item?}', [ServiceController::class, 'save'])->name('save');
    });

    // MIGRATED to Livewire SFC — resources/views/livewire/gopanel/product/*
    Route::prefix('products')->name('products.')->group(function () {
        Route::livewire('/',                'gopanel.product.index')->name('index');
        Route::livewire('/create',          'gopanel.product.create')->name('create');
        Route::livewire('/{product}/edit',  'gopanel.product.edit')->name('edit');
    });

    // @deprecated — legacy AJAX endpoints (retains SEO meta save flow)
    Route::prefix('products-legacy')->name('products.legacy.')->group(function () {
        Route::get('/store/{item:uid?}', [ProductController::class, 'store'])->name('store');
        Route::post('/save/{item:uid?}', [ProductController::class, 'save'])->name('save');
    });

    // MIGRATED to Livewire SFC — resources/views/livewire/gopanel/slider/index.blade.php
    Route::prefix('sliders')->name('sliders.')->group(function () {
        Route::livewire('/', 'gopanel.slider.index')->name('index');
    });

    // @deprecated — kept for backwards compatibility, use gopanel.sliders.* instead
    Route::prefix('slider')->name('slider.')->group(function () {
        Route::get('/', [SliderController::class, 'index'])->name('index');
        Route::get('/get/form/{item?}', [SliderController::class, 'getForm'])->name('get.form');
        Route::post('/save/{item?}', [SliderController::class, 'save'])->name('save');
    });

    // @deprecated duplicate contact group — remove in Phase 8.
    // Active routes are at top of file (Livewire SFC). Endpoints below kept as orphans for now.
    /* Route::prefix('contact')->name('contact.')->group(function () {
        Route::prefix('contact-info')->name('contact-info.')->group(function () {
            Route::get('/{item?}', [ContactInfoController::class, 'index'])->name('index');
            Route::post('/save/{item?}', [ContactInfoController::class, 'save'])->name('save.form');
        });

        Route::prefix('socials')->name('socials.')->group(function () {
            Route::get('/', [SocialController::class, 'index'])->name('index');
            Route::get('/get/form/{item?}', [SocialController::class, 'getForm'])->name('get.form');
            Route::post('/save/{item?}', [SocialController::class, 'save'])->name('save');
        });
    }); */

    // MIGRATED to Livewire SFC — Activity logs + File logs
    Route::prefix('activity')->name('activity.')->group(function () {
        Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
            Route::livewire('/', 'gopanel.activity-log.index')->name('index');
        });

        Route::prefix('file-logs')->name('file-logs.')->group(function () {
            Route::livewire('/', 'gopanel.file-log.index')->name('index');
        });
    });

    // @deprecated — legacy AJAX endpoints (view modal, user select2)
    Route::prefix('activity-legacy')->name('activity.legacy.')->group(function () {
        Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
            Route::get('/view/{item}', [ActivityLogController::class, 'view'])->name('view');
            Route::get('/users', [ActivityLogController::class, 'getUsers'])->name('users');
        });

        Route::prefix('file-logs')->name('file-logs.')->group(function () {
            Route::get('/view/{item}', [FileLogController::class, 'view'])->name('view');
            Route::get('/users', [FileLogController::class, 'getUsers'])->name('users');
        });
    });

    // System
    Route::prefix('system')->name('system.')->group(function () {
        Route::prefix('updates')->name('updates.')->group(function () {
            Route::get('/', [UpdateController::class, 'index'])->name('index');
            Route::post('/check', [UpdateController::class, 'check'])->name('check');
            Route::post('/diff', [UpdateController::class, 'diff'])->name('diff');
            Route::post('/apply', [UpdateController::class, 'apply'])->name('apply');
            Route::post('/rollback', [UpdateController::class, 'rollback'])->name('rollback');
            Route::post('/history-diff', [UpdateController::class, 'historyDiff'])->name('history-diff');
            Route::post('/rollback-file', [UpdateController::class, 'rollbackFile'])->name('rollback-file');
        });
    });
});
