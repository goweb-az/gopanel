<?php

/**
 * Xəritə köməkçiləri - `App\Support\Geo\StaticMap`.
 */
return [

    /*
     * Email/bildiriş şablonlarındakı statik xəritə ŞƏKLİ.
     *
     *   none     - şəkil qurulmur (`image()` null), yalnız «Xəritədə aç» linki;
     *   google   - Google Static Maps (açar lazımdır);
     *   geoapify - Geoapify Static Maps (açar lazımdır, pulsuz limit genişdir).
     *
     * Niyə default `none`: açarsız ictimai statik xəritə servisləri bağlanıb -
     * açarsız provayder seçilsə email-də sınıq şəkil görünərdi. Açar tələb edən
     * provayder açarsız qalıbsa da `none`-a düşülür.
     */
    'static_map' => [
        'provider' => env('STATIC_MAP_PROVIDER', 'none'),
        'key'      => env('STATIC_MAP_KEY', ''),
    ],

];
