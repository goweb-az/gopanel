<?php

return [
    'home' => [
        'class'     => App\Http\Controllers\Site\HomeController::class,
        'method'    => "index",
        'name'      => 'home.index'
    ],
    'blogs' => [
        'class'     => App\Http\Controllers\Site\BlogController::class,
        'method'    => "index",
        'name'      => 'blog.index',
    ],
    'contact' => [
        'class'     => App\Http\Controllers\Site\StaticPageController::class,
        'method'    => "contact",
        'name'      => 'contact.index',
        // 'param'     => '/{slug?}' ehtiyyac yaranarsa buradan parametirde gondermek mumkundur
    ],
];
