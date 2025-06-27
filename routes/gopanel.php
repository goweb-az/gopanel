<?php

use App\Http\Controllers\Gopanel\Activity\FileLogsController;
use App\Http\Controllers\Gopanel\Activity\HistoryController;
use App\Http\Controllers\Gopanel\Admins\AdminController;
use App\Http\Controllers\Gopanel\Admins\RoleController;
use App\Http\Controllers\Gopanel\AuthController;
use App\Http\Controllers\Gopanel\BlogController;
use App\Http\Controllers\Gopanel\Communications\MessageTemplateController;
use App\Http\Controllers\Gopanel\DashboardController;
use App\Http\Controllers\Gopanel\DatatableController;
use App\Http\Controllers\Gopanel\Common\GeneralController;
use App\Http\Controllers\Gopanel\Settings\MailSettingsController;
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

    // Site Settings 
    Route::prefix('site-settings')->name("site-settings.")->group(function () {
        Route::get('/{item?}', [SiteSettingsController::class, 'index'])->name('index');
        Route::post('/save/{item?}', [SiteSettingsController::class, 'save'])->name('save.form');
    });

    Route::prefix('languages')->name("languages.")->group(function () {
        Route::get('/', [LanguageController::class, 'index'])->name('index');
        Route::get('/get/form/{item?}', [LanguageController::class, 'getForm'])->name('get.form');
        Route::post('/save/{item?}', [LanguageController::class, 'save'])->name('save');
    });

    //Translates
    Route::prefix('translations')->name("translations.")->group(function () {
        Route::get('/', [TranslationController::class, 'index'])->name('index');
        Route::get('/get/form/{item?}', [TranslationController::class, 'getForm'])->name('get.form');
        Route::post('/save/form/{item?}', [TranslationController::class, 'save'])->name('save.form');
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

    //Activity
    Route::prefix('activity')->name("activity.")->group(function () {
        //tarixce
        Route::prefix('history')->name("history.")->group(function () {
            Route::get('/', [HistoryController::class, 'index'])->name('index');
            Route::get('/show/{history}', [HistoryController::class, 'show'])->name('show');
        });

        //Logs
        Route::prefix('file-logs')->name("file-logs.")->group(function () {
            Route::get('/', [FileLogsController::class, 'index'])->name('index');
            Route::get('/show/{log}', [FileLogsController::class, 'show'])->name('show');
        });
    });
});
