<?php

use App\Http\Controllers\Gopanel\AuthController;
use App\Http\Controllers\Gopanel\DatatableController;
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
// Login: GET render + POST authenticate handled inside the Livewire SFC
Route::livewire('/auth/login', 'gopanel.auth.login')->name('auth.login');
// Logout: stateless GET that kills the session — kept on a controller
Route::get('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

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

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::livewire('/site-settings', 'gopanel.settings.site-settings.index')->name('site-settings.index');
        Route::livewire('/languages', 'gopanel.settings.language.index')->name('languages.index');
        Route::livewire('/translations', 'gopanel.settings.translation.index')->name('translations.index');

        Route::prefix('menu')->name('menu.')->group(function () {
            Route::livewire('/', 'gopanel.settings.menu.index')->name('index');
            Route::livewire('/create', 'gopanel.settings.menu.create')->name('create');
            Route::livewire('/{menu}/edit', 'gopanel.settings.menu.edit')->name('edit');
        });
    });

    Route::prefix('contact')->name('contact.')->group(function () {
        Route::livewire('/contact-info', 'gopanel.contact.contact-info.index')->name('contact-info.index');
        Route::livewire('/socials', 'gopanel.contact.social.index')->name('socials.index');
    });

    Route::prefix('seo')->name('seo.')->group(function () {
        Route::livewire('/site-redirects', 'gopanel.seo.site-redirect.index')->name('site-redirects.index');
        Route::livewire('/seo-analytics', 'gopanel.seo.seo-analytics.index')->name('seo-analytics.index');
        Route::livewire('/llms-txt', 'gopanel.seo.llms-txt.index')->name('llms-txt.index');
    });

    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::livewire('/', 'gopanel.analytics.index')->name('index');
        Route::prefix('detail')->name('detail.')->group(function () {
            Route::livewire('/devices', 'gopanel.analytics.detail.devices')->name('devices');
            Route::livewire('/operating-systems', 'gopanel.analytics.detail.operating-systems')->name('operating.systems');
            Route::livewire('/browsers', 'gopanel.analytics.detail.browsers')->name('browsers');
            Route::livewire('/countries', 'gopanel.analytics.detail.countries')->name('countries');
            Route::livewire('/cities', 'gopanel.analytics.detail.cities')->name('cities');
            Route::livewire('/languages', 'gopanel.analytics.detail.languages')->name('languages');
            Route::livewire('/clicks', 'gopanel.analytics.detail.clicks')->name('clicks');
            Route::livewire('/links', 'gopanel.analytics.detail.links')->name('links');
            Route::livewire('/utm/parameters', 'gopanel.analytics.detail.utm-parameters')->name('utm.parameters');
            Route::livewire('/ad-platforms', 'gopanel.analytics.detail.ad-platforms')->name('ad.platforms');
            Route::livewire('/ad-platform-data', 'gopanel.analytics.detail.ad-platform-data')->name('ad.platform.data');
        });
    });

    Route::livewire('/admins', 'gopanel.admin.index')->name('admins.index');
    Route::livewire('/admins/create', 'gopanel.admin.create')->name('admins.create');
    Route::livewire('/admins/{admin}/edit', 'gopanel.admin.edit')->name('admins.edit');

    Route::livewire('/admins/roles', 'gopanel.admin.roles.index')->name('admins.roles.index');
    Route::livewire('/admins/roles/create', 'gopanel.admin.roles.create')->name('admins.roles.create');
    Route::livewire('/admins/roles/{role}/edit', 'gopanel.admin.roles.edit')->name('admins.roles.edit');

    Route::livewire('/profile', 'gopanel.profile.index')->name('profile.index');
    Route::livewire('/profile/change-password', 'gopanel.profile.change-password')->name('profile.change-password.index');

    Route::livewire('/categories', 'gopanel.category.index')->name('categories.index');

    Route::livewire('/blog', 'gopanel.blog.index')->name('blog.index');
    Route::livewire('/blog/create', 'gopanel.blog.create')->name('blog.create');
    Route::livewire('/blog/{blog}/edit', 'gopanel.blog.edit')->name('blog.edit');

    Route::livewire('/about-us', 'gopanel.about-us.index')->name('about-us.index');

    Route::livewire('/services', 'gopanel.service.index')->name('services.index');

    Route::livewire('/products', 'gopanel.product.index')->name('products.index');
    Route::livewire('/products/create', 'gopanel.product.create')->name('products.create');
    Route::livewire('/products/{product}/edit', 'gopanel.product.edit')->name('products.edit');

    Route::livewire('/sliders', 'gopanel.slider.index')->name('sliders.index');

    Route::prefix('activity')->name('activity.')->group(function () {
        Route::livewire('/activity-logs', 'gopanel.activity.activity-log.index')->name('activity-logs.index');
        Route::livewire('/file-logs', 'gopanel.activity.file-log.index')->name('file-logs.index');
    });

    Route::livewire('/system/updates', 'gopanel.system.updates.index')->name('system.updates.index');

    // Dev tools — local only (Horizon/Telescope/Pulse/Mailpit)
    if (app()->environment('local')) {
        Route::prefix('dev')->name('dev.')->group(function () {
            Route::livewire('/horizon', 'gopanel.dev.horizon')->name('horizon');
            Route::livewire('/telescope', 'gopanel.dev.telescope')->name('telescope');
            Route::livewire('/pulse', 'gopanel.dev.pulse')->name('pulse');
            Route::livewire('/mail-server', 'gopanel.dev.mail-server')->name('mail-server');
        });
    }
});
