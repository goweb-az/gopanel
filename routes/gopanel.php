<?php

use App\Http\Controllers\Gopanel\AuthController;
use App\Http\Controllers\Gopanel\Common\GeneralController;
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

    // General endpoints still consumed by GoPanelHelper status/edit/delete buttons
    // and the icon picker modal.
    Route::prefix('general')->name('general.')->group(function () {
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

    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::livewire('/', 'gopanel.analytics.index')->name('index');
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

    Route::prefix('system')->name('system.')->group(function () {
        Route::prefix('updates')->name('updates.')->group(function () {
            Route::livewire('/', 'gopanel.system-updates.index')->name('index');
        });
    });
});
