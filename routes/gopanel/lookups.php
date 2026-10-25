<?php

/*
|--------------------------------------------------------------------------
| Status və Tiplər (lookup) route-ları
|--------------------------------------------------------------------------
|
| routes/gopanel.php bu faylı `gopanel` middleware qrupunun İÇİNDƏ qoşur,
| ona görə ad prefiksi `gopanel.` və URL prefiksi `/gopanel` artıq var.
|
| Route-lar `config/gopanel/lookups.php` reyestrindən qurulur - yeni tip üçün
| burada kod yazılmır. Hər tip eyni 6 route-u alır:
|
|   GET    lookups/{tip}                 .index     gopanel.can: index
|   GET    lookups/{tip}/get/form/{id?}  .get.form  gopanel.can: add | edit
|   POST   lookups/{tip}/save/{id?}      .save      gopanel.can: add | edit
|   DELETE lookups/{tip}/delete/{id}     .destroy   gopanel.can: delete
|   PATCH  lookups/{tip}/sort            .sort      gopanel.can: sort
|   POST   lookups/{tip}/toggle          .toggle    gopanel.can: edit
|
| Forma/saxla route-u «hər hansı biri» ilə açılır, konkret add/edit isə
| controller və FormRequest-də yoxlanılır (yalnız `add` icazəsi olan admin
| mövcud sətri redaktə edə bilməsin).
|
*/

use App\Http\Controllers\Gopanel\Lookups\LookupSectionController;
use App\Services\Gopanel\Lookup\LookupRegistry;
use Illuminate\Support\Facades\Route;

$lookupTypes = LookupRegistry::types();

if ($lookupTypes === []) {
    return;
}

Route::prefix('lookups')->name('lookups.')->group(function () use ($lookupTypes) {
    Route::get('/', [LookupSectionController::class, 'index'])
        ->middleware('gopanel.can:' . implode(',', LookupRegistry::indexPermissions()))
        ->name('index');

    foreach ($lookupTypes as $type => $entry) {
        $controller = $entry['controller'];
        $can = static fn (string ...$actions): string => 'gopanel.can:' . implode(',', array_map(
            static fn (string $action) => LookupRegistry::permission($type, $action),
            $actions,
        ));

        Route::prefix($type)->name($type . '.')->group(function () use ($controller, $can) {
            Route::get('/', [$controller, 'index'])->middleware($can('index'))->name('index');
            Route::get('/get/form/{id?}', [$controller, 'getForm'])->middleware($can('add', 'edit'))->name('get.form');
            Route::post('/save/{id?}', [$controller, 'save'])->middleware($can('add', 'edit'))->name('save');
            Route::delete('/delete/{id}', [$controller, 'destroy'])->middleware($can('delete'))->name('destroy');
            Route::patch('/sort', [$controller, 'sort'])->middleware($can('sort'))->name('sort');
            Route::post('/toggle', [$controller, 'toggle'])->middleware($can('edit'))->name('toggle');
        });
    }
});
