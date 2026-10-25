<?php

/*
|--------------------------------------------------------------------------
| Status və Tiplər (lookup) reyestri
|--------------------------------------------------------------------------
|
| Kiçik sorğu cədvəlləri (sifariş statusları, məhsul tipləri, şikayət
| səbəbləri...) - adətən 5-10 sətir, admin özü əlavə edir, sıralayır, söndürür.
|
| NİYƏ REYESTR: əvvəlki layihələrdə hər yeni tip üçün 5 route, menyu sətri,
| icazə sətirləri və sidebar elementi əl ilə yazılırdı və hər dəfə biri
| unudulurdu (məs. route var, icazə yox). İndi bu fayldakı BİR sətir:
|
|   - routes/gopanel/lookups.php      → index / form / save / delete / sort / toggle
|   - config/gopanel/permission_list  → gopanel.lookups.{tip}.index|add|edit|delete|sort
|   - bölmədaxili sidebar             → icazəsi olan tiplər
|   - əsas sol menyu                  → «Status və Tiplər» (reyestr boşdursa görünmür)
|
| Starter-də heç bir tip YOXDUR (layihəyə xas iş məntiqi bura yazılmır).
| Yeni tip: `php artisan gopanel:make-lookup OrderStatus` - komanda bu fayla
| sətri özü əlavə edir. Ətraflı: docs/rules/gopanel-status-and-type-cruds.md
|
| Əvvəlcə soruş: dəyər dəsti kod tərəfindən idarə olunursa (admin dəyişmirsə)
| o, lookup deyil, Enum-dur (app/Enums).
|
*/

return [

    // Əsas sol menyudakı bölmə elementi
    'menu' => [
        'title' => 'Status və Tiplər',
        'icon'  => '<i class="bx bx-category-alt"></i>',
    ],

    /*
     * Tip açarı (URL seqmenti, kebab-case) => tərif.
     *
     *   'title'      - sidebar və səhifə başlığı (Azərbaycan dilində)
     *   'controller' - BaseLookupController törəməsi
     *   'icon'       - bölmədaxili sidebar ikonu (Boxicons/FA sinfi)
     *
     * Nümunə:
     *   'order-statuses' => [
     *       'title'      => 'Sifariş statusları',
     *       'controller' => \App\Http\Controllers\Gopanel\Lookups\OrderStatusController::class,
     *       'icon'       => 'bx bx-list-check',
     *   ],
     */
    'types' => [
        // gopanel:make-lookup:types
    ],

];
