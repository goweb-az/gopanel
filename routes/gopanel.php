<?php

use App\Http\Controllers\Gopanel\AuthController;
use App\Http\Controllers\Gopanel\Common\GeneralController;
use App\Http\Controllers\Gopanel\DatatableController;
use App\Http\Controllers\Gopanel\Seo\AnalyticsController;
use App\Http\Controllers\Gopanel\System\UpdateController;
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

// Auth Proccess
Route::prefix('auth')->name('auth.')->group(function () {
    // Login: GET render + POST authenticate handled inside the Livewire SFC
    Route::livewire('/login', 'gopanel.auth.login')->name('login');
    // Logout: stateless GET that kills the session — kept on a controller
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Start Gopanel route group
Route::group(['middleware' => 'gopanel'], function () {

    // Dashboard
    Route::livewire('/', 'gopanel.dashboard.index')->name('index');

    // Livewire smoke tests (TEMP — remove before production)
    Route::livewire('/_lw-probe', 'test-probe')->name('_lw.probe');
    Route::livewire('/_dt-probe', 'dt-probe')->name('_dt.probe');

    // Legacy server-side datatable handler (still wired by App\Datatable\Gopanel\*
    // until those classes are deleted alongside the migrated controllers)
    Route::get('datatable/{table}', [DatatableController::class, 'handle'])->name('datatable.source');

    // Legacy crud.js endpoints (sortable, status toggle, generic delete/edit/...)
    // still consumed by the few non-Livewire pages: dashboard, analytics, system.
    Route::prefix('general')->name('general.')->group(function () {
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
        Route::prefix('site-settings')->name('site-settings.')->group(function () {
            Route::livewire('/', 'gopanel.site-settings.index')->name('index');
        });

        Route::prefix('menu')->name('menu.')->group(function () {
            Route::livewire('/',                 'gopanel.menu.index')->name('index');
            Route::livewire('/create',           'gopanel.menu.create')->name('create');
            Route::livewire('/{menu}/edit',      'gopanel.menu.edit')->name('edit');
        });

        Route::prefix('languages')->name('languages.')->group(function () {
            Route::livewire('/', 'gopanel.language.index')->name('index');
        });

        Route::prefix('translations')->name('translations.')->group(function () {
            Route::livewire('/', 'gopanel.translation.index')->name('index');
        });
    });

    Route::prefix('contact')->name('contact.')->group(function () {
        Route::prefix('contact-info')->name('contact-info.')->group(function () {
            Route::livewire('/', 'gopanel.contact-info.index')->name('index');
        });

        Route::prefix('socials')->name('socials.')->group(function () {
            Route::livewire('/', 'gopanel.social.index')->name('index');
        });
    });

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

    // Analytics: pages are Livewire SFCs; the chart/search AJAX endpoints
    // remain on the controllers because analytics.js drives them directly.
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::livewire('/', 'gopanel.analytics.index')->name('index');
        Route::get('/get/top-hits', [AnalyticsController::class, 'getTopHits'])->name('get.top.hits');
        Route::get('/get/countries-map', [AnalyticsController::class, 'getCountriesMap'])->name('get.countries.map');
        Route::get('/get/cities-chart', [AnalyticsController::class, 'getCitiesChart'])->name('cities.chart');
        Route::get('/get/languages-chart', [AnalyticsController::class, 'getLanguagesChart'])->name('get.languages');
        Route::get('/get/os-chart', [AnalyticsController::class, 'getOperatingSystemsChart'])->name('os.chart');
        Route::get('/api/countries', [AnalyticsController::class, 'searchCountries'])->name('api.countries');
        Route::get('/api/cities', [AnalyticsController::class, 'searchCities'])->name('api.cities');
        Route::prefix('detail')->name('detail.')->group(function () {
            Route::livewire('/devices',           'gopanel.analytics.detail.devices')->name('devices');
            Route::livewire('/operating-systems', 'gopanel.analytics.detail.operating_systems')->name('operating.systems');
            Route::livewire('/browsers',          'gopanel.analytics.detail.browsers')->name('browsers');
            Route::livewire('/countries',         'gopanel.analytics.detail.countries')->name('countries');
            Route::livewire('/cities',            'gopanel.analytics.detail.cities')->name('cities');
            Route::livewire('/languages',         'gopanel.analytics.detail.languages')->name('languages');
            Route::livewire('/clicks',            'gopanel.analytics.detail.clicks')->name('clicks');
            Route::livewire('/links',             'gopanel.analytics.detail.links')->name('links');
            Route::livewire('/utm/parameters',    'gopanel.analytics.detail.utm_parameters')->name('utm.parameters');
            Route::livewire('/ad-platforms',      'gopanel.analytics.detail.ad_platforms')->name('ad.platforms');
            Route::livewire('/ad-platform-data',  'gopanel.analytics.detail.ad_platform_data')->name('ad.platform.data');
        });
    });

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

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::livewire('/',                'gopanel.profile.index')->name('index');
        Route::livewire('/change-password', 'gopanel.profile.change-password')->name('change-password.index');
    });

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::livewire('/', 'gopanel.category.index')->name('index');
    });

    Route::prefix('blog')->name('blog.')->group(function () {
        Route::livewire('/',                'gopanel.blog.index')->name('index');
        Route::livewire('/create',          'gopanel.blog.create')->name('create');
        Route::livewire('/{blog}/edit',     'gopanel.blog.edit')->name('edit');
    });

    Route::prefix('about-us')->name('about-us.')->group(function () {
        Route::livewire('/', 'gopanel.about-us.index')->name('index');
    });

    Route::prefix('services')->name('services.')->group(function () {
        Route::livewire('/', 'gopanel.service.index')->name('index');
    });

    Route::prefix('products')->name('products.')->group(function () {
        Route::livewire('/',                'gopanel.product.index')->name('index');
        Route::livewire('/create',          'gopanel.product.create')->name('create');
        Route::livewire('/{product}/edit',  'gopanel.product.edit')->name('edit');
    });

    Route::prefix('sliders')->name('sliders.')->group(function () {
        Route::livewire('/', 'gopanel.slider.index')->name('index');
    });

    Route::prefix('activity')->name('activity.')->group(function () {
        Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
            Route::livewire('/', 'gopanel.activity-log.index')->name('index');
        });

        Route::prefix('file-logs')->name('file-logs.')->group(function () {
            Route::livewire('/', 'gopanel.file-log.index')->name('index');
        });
    });

    // System updates: index is a Livewire SFC; the AJAX endpoints still
    // live on the controller because updater.js drives them directly.
    Route::prefix('system')->name('system.')->group(function () {
        Route::prefix('updates')->name('updates.')->group(function () {
            Route::livewire('/', 'gopanel.system-updates.index')->name('index');
            Route::post('/check', [UpdateController::class, 'check'])->name('check');
            Route::post('/diff', [UpdateController::class, 'diff'])->name('diff');
            Route::post('/apply', [UpdateController::class, 'apply'])->name('apply');
            Route::post('/rollback', [UpdateController::class, 'rollback'])->name('rollback');
            Route::post('/history-diff', [UpdateController::class, 'historyDiff'])->name('history-diff');
            Route::post('/rollback-file', [UpdateController::class, 'rollbackFile'])->name('rollback-file');
        });
    });
});
