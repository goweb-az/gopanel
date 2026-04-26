<?php

use App\Http\Controllers\Site\DynamicContentController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\Seo\RssController;
use App\Http\Controllers\Site\Seo\SitemapController;
use App\Http\Controllers\Site\Seo\TxtController;
use App\Http\Controllers\Site\StaticPageController;
use App\Http\Controllers\TestController;
use App\Models\Geography\Language;
use App\Models\Navigation\Menu;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

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


// Route::get('/test', [TestController::class, 'index'])->name('index');

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [HomeController::class, 'index'])->middleware(['language'])->name('home.index');

Route::withoutMiddleware(['site.redirects', 'track.analytics'])->group(function () {
    Route::get('/test', [TestController::class, 'index'])->name('test.index');
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get('/rss-index.opml', [RssController::class, 'index'])->name('site.rss.index');
    Route::get('/robots.txt', [TxtController::class, 'robots'])->name('robots.txt');
    Route::get('/ai.txt', [TxtController::class, 'ai'])->name('ai.txt');
    Route::get('/llms.txt', [TxtController::class, 'llms'])->name('llms.txt');
});

if (Schema::hasTable('languages')) {
    try {
        foreach (Language::getCachedAll() as $language) {
            //Sitemap
            Route::get("{$language->code}/sitemap.xml", [SitemapController::class, 'single'])->name("site.{$language->code}.sitemap.single")
                ->withoutMiddleware(['site.redirects', 'track.analytics']);
            // Rss 
            Route::get("/{$language->code}/rss.xml", [RssController::class, 'single'])->name("site.{$language->code}.rss.single")
                ->withoutMiddleware(['site.redirects', 'track.analytics']);
            //Site pages
            Route::prefix($language->code)->name("site.{$language->code}.")->middleware(['language'])->group(function () use ($language) {
                Route::get('/', [HomeController::class, 'index'])->name('home.index');
                foreach (Menu::getRoutes($language->code) as $menu) {
                    if (!is_null($menu->config)) {
                        $prefix = "{$menu->route_slug}" . (isset($menu->config['param']) ? "{$menu->config['param']}" : null);
                        Route::get($prefix, [$menu->config['class'], $menu->config['method']])->name("{$menu->config['name']}");
                    }
                }
                // Bu legal pagesden gelecek
                Route::get('/404', [StaticPageController::class, 'fallback'])->name('404.index');
                //Diger butun rouutlar ucun detail sehifeleri
                Route::get('/{slug}', [DynamicContentController::class, 'index'])->name('dynamic.data');
                // Aktiv diller ucun calishsin ancaq
            })->where('language', Language::getActiveCodesForRouteRegex());
        }
    } catch (\Exception $e) {
        // Migration zamanı cədvəl yoxdursa sessiyaca keçir
    }
}

// Fallback Route
// Route::fallback([FallBackController::class, 'fallback']);
Route::fallback(function () {
    return redirect('404');
});
