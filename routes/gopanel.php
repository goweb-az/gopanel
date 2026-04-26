<?php

use App\Http\Controllers\Gopanel\Activity\FileLogController;
use App\Http\Controllers\Gopanel\Activity\ActivityLogController;
use App\Http\Controllers\Gopanel\System\UpdateController;
use App\Http\Controllers\Gopanel\Admins\AdminController;
use App\Http\Controllers\Gopanel\Admins\ProfileController;
use App\Http\Controllers\Gopanel\Admins\RoleController;
use App\Http\Controllers\Gopanel\AuthController;
use App\Http\Controllers\Gopanel\BlogController;
use App\Http\Controllers\Gopanel\Communications\MessageTemplateController;
use App\Http\Controllers\Gopanel\DashboardController;
use App\Http\Controllers\Gopanel\DatatableController;
use App\Http\Controllers\Gopanel\Common\GeneralController;
use App\Http\Controllers\Gopanel\Contact\ContactInfoController;
use App\Http\Controllers\Gopanel\Contact\SocialController;
use App\Http\Controllers\Gopanel\Seo\SeoAnalyticsController;
use App\Http\Controllers\Gopanel\Seo\SiteRedirectController;
use App\Http\Controllers\Gopanel\Seo\LlmsTxtController;
use App\Http\Controllers\Gopanel\Settings\MailSettingsController;
use App\Http\Controllers\Gopanel\Settings\MenuController;
use App\Http\Controllers\Gopanel\Settings\SiteSettingsController;
use App\Http\Controllers\Gopanel\Settings\SubscriptionDurationController;
use App\Http\Controllers\Gopanel\SliderController;
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

//Auth Proccess
Route::prefix('auth')->name("auth.")->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('login', [AuthController::class, 'attempt'])->name('login.proccess');
});


// Start Gopanel route group 
Route::group(['middleware' => 'gopanel'], function () {

    //Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    //Datatable
    Route::get('datatable/{table}', [DatatableController::class, 'handle'])->name('datatable.source');

    //General default routes
    Route::prefix('general')->name("general.")->group(function () {
        Route::get('/clear/cache', [GeneralController::class, 'clearCache'])->name('clear.cache');
        Route::get('/get/route', [GeneralController::class, 'route'])->name('get.route');
        Route::post('/status/change', [GeneralController::class, 'statusChange'])->name('status.change');
        Route::post('/sortable', [GeneralController::class, 'sortable'])->name('sortable');
        Route::post('/add', [GeneralController::class, 'add'])->name('add');
        Route::post('/delete/{id?}', [GeneralController::class, 'delete'])->name('delete');
        Route::post('/archive/{id?}', [GeneralController::class, 'archive'])->name('archive');
        Route::post('/edit/{id?}', [GeneralController::class, 'edit'])->name('edit');
        Route::post('/editable/{id?}', [GeneralController::class, 'editable'])->name('editable');
    });


    Route::prefix('settings')->name("settings.")->group(function () {
        // Site Settings 
        Route::prefix('site-settings')->name("site-settings.")->group(function () {
            Route::get('/{item?}', [SiteSettingsController::class, 'index'])->name('index');
            Route::post('/save/{item?}', [SiteSettingsController::class, 'save'])->name('save.form');
        });

        // Route::prefix('media')->name("media.")->group(function () {
        //     Route::get('/{type}/{id}', [MediaController::class, 'index'])->name('index');
        //     Route::post('/upload/{type}/{id}', [MediaController::class, 'upload'])->name('upload');
        //     Route::delete('/delete/{media}', [MediaController::class, 'delete'])->name('delete');
        // });


        Route::prefix('menu')->name("menu.")->group(function () {
            Route::get('/', [MenuController::class, 'index'])->name('index');
            Route::get('/store/{item?}', [MenuController::class, 'store'])->name('store');
            Route::post('/save/{item?}', [MenuController::class, 'save'])->name('save');
        });

        Route::prefix('languages')->name("languages.")->group(function () {
            Route::get('/', [LanguageController::class, 'index'])->name('index');
            Route::get('/get/form/{item?}', [LanguageController::class, 'getForm'])->name('get.form');
            Route::post('/save/{item?}', [LanguageController::class, 'save'])->name('save');
            Route::post('/toggle-default', [LanguageController::class, 'toggleDefault'])->name('toggle.default');
        });

        //Translates
        Route::prefix('translations')->name("translations.")->group(function () {
            Route::get('/', [TranslationController::class, 'index'])->name('index');
            Route::get('/get/form/{item?}', [TranslationController::class, 'getForm'])->name('get.form');
            Route::post('/save/form/{item?}', [TranslationController::class, 'save'])->name('save.form');
        });
    });

    Route::prefix('contact')->name("contact.")->group(function () {
        Route::prefix('contact-info')->name("contact-info.")->group(function () {
            Route::get('/{item?}', [ContactInfoController::class, 'index'])->name('index');
            Route::post('/save/{item?}', [ContactInfoController::class, 'save'])->name('save.form');
        });

        Route::prefix('socials')->name("socials.")->group(function () {
            Route::get('/', [SocialController::class, 'index'])->name('index');
            Route::get('/get/form/{item?}', [SocialController::class, 'getForm'])->name('get.form');
            Route::post('/save/{item?}', [SocialController::class, 'save'])->name('save');
        });
    });


    Route::prefix('seo')->name("seo.")->group(function () {
        Route::prefix('site-redirects')->name("site-redirects.")->group(function () {
            Route::get('/', [SiteRedirectController::class, 'index'])->name('index');
            Route::get('/get/form/{item?}', [SiteRedirectController::class, 'getForm'])->name('get.form');
            Route::post('/save/{item?}', [SiteRedirectController::class, 'save'])->name('save');
        });

        Route::prefix('seo-analytics')->name("seo-analytics.")->group(function () {
            Route::get('/{item?}', [SeoAnalyticsController::class, 'index'])->name('index');
            Route::post('/save/{item?}', [SeoAnalyticsController::class, 'save'])->name('save.form');
        });

        Route::prefix('llms-txt')->name("llms-txt.")->group(function () {
            Route::get('/{item?}', [LlmsTxtController::class, 'index'])->name('index');
            Route::post('/save/{item?}', [LlmsTxtController::class, 'save'])->name('save.form');
        });
    });


    //Admins
    Route::prefix('admins')->name("admins.")->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/get/form/{item?}', [AdminController::class, 'getForm'])->name('get.form');
        Route::post('/save/{item?}', [AdminController::class, 'save'])->name('save');

        Route::prefix('roles')->name("roles.")->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('/store/{item?}', [RoleController::class, 'store'])->name('store');
            Route::post('/save/{item?}', [RoleController::class, 'save'])->name('save');
        });
    });

    //Profile
    Route::prefix('profile')->name("profile.")->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::post('/update', [ProfileController::class, 'update'])->name('update');
        Route::get('/change-password', [ProfileController::class, 'changePasswordIndex'])->name('change-password.index');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('change-password');
    });

    //Blog
    Route::prefix('blog')->name("blog.")->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/store/{item?}', [BlogController::class, 'store'])->name('store');
        Route::post('/save/{item?}', [BlogController::class, 'save'])->name('save');
    });


    Route::prefix('slider')->name("slider.")->group(function () {
        Route::get('/', [SliderController::class, 'index'])->name('index');
        Route::get('/get/form/{item?}', [SliderController::class, 'getForm'])->name('get.form');
        Route::post('/save/{item?}', [SliderController::class, 'save'])->name('save');
    });

    //Contact
    Route::prefix('contact')->name("contact.")->group(function () {
        Route::prefix('contact-info')->name("contact-info.")->group(function () {
            Route::get('/{item?}', [ContactInfoController::class, 'index'])->name('index');
            Route::post('/save/{item?}', [ContactInfoController::class, 'save'])->name('save.form');
        });

        Route::prefix('socials')->name("socials.")->group(function () {
            Route::get('/', [SocialController::class, 'index'])->name('index');
            Route::get('/get/form/{item?}', [SocialController::class, 'getForm'])->name('get.form');
            Route::post('/save/{item?}', [SocialController::class, 'save'])->name('save');
        });
    });

    //Activity
    Route::prefix('activity')->name("activity.")->group(function () {
        //tarixce
        Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index'])->name('index');
            Route::get('/view/{item}', [ActivityLogController::class, 'view'])->name('view');
            Route::post('/delete/{item}', [ActivityLogController::class, 'delete'])->name('delete');
            Route::post('/cleanup', [ActivityLogController::class, 'cleanup'])->name('cleanup');
            Route::get('/users', [ActivityLogController::class, 'getUsers'])->name('users');
        });

        //Logs
        // ─── File Logs ────────────────────────────────────────────────
        Route::prefix('file-logs')->name("file-logs.")->group(function () {
            Route::get('/', [FileLogController::class, 'index'])->name('index');
            Route::get('/view/{item}', [FileLogController::class, 'view'])->name('view');
            Route::post('/delete/{item}', [FileLogController::class, 'delete'])->name('delete');
            Route::post('/cleanup', [FileLogController::class, 'cleanup'])->name('cleanup');
            Route::get('/users', [FileLogController::class, 'getUsers'])->name('users');
        });
    });

    // System
    Route::prefix('system')->name("system.")->group(function () {
        Route::prefix('updates')->name("updates.")->group(function () {
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
