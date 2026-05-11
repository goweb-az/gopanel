<?php


return [

    /*
    |--------------------------------------------------------------------------
    | Storage disk (single source of truth)
    |--------------------------------------------------------------------------
    | Default disk used for every Gopanel write that previously hard-coded
    | 'public'. Override per-environment via GOPANEL_STORAGE_DISK so we never
    | sprinkle disk names through Storage::disk('...')->... call sites.
    */
    'storage' => [
        'disk' => env('GOPANEL_STORAGE_DISK', 'public'),
    ],

    'faq-types' => [
        'web' => 'Veb sayt',
        'mobile' => 'Mobil App',
        'panel' => 'Müştəri Paneli',
        'other' => 'Digər',
    ],

];
