<?php

return [
    [
        'title' => 'Əsas'
    ],
    [
        'icon' => '<i class="bx bx-home-circle"></i>',
        'title' => 'Əsas səhifə',
        'route' => 'gopanel.index',
        'can'   => 'gopanel.index',
    ],
    [
        'icon' => '<i class="fas fa-cogs"></i>',
        'title' => 'Tənzimləmələr',
        'can'   => 'gopanel.settings.index',
        'inner' => [
            [
                'icon' => '<i class="fas fa-cog"></i>',
                'title' => 'Əsas Tənzimləmələr',
                'route' => 'gopanel.site-settings.index',
                'can'   => 'gopanel.site-settings.index',
            ],
            [
                'icon' => '<i class="fas fa-language"></i>',
                'title' => 'Tərcümələr',
                'route' => 'gopanel.translations.index',
                'can'   => 'gopanel.translations.index',
            ],
        ],
    ],
    [
        'title' => 'İdaretmə'
    ],
    [
        'icon' => '<i class="bx bx-home-circle"></i>',
        'title' => 'Adminlər',
        'route' => 'gopanel.admins.index',
        'can'   => 'gopanel.admins.index',
    ],
    [
        'icon' => '<i class="bx bx-home-circle"></i>',
        'title' => 'Vəzifələr',
        'route' => 'gopanel.admins.roles.index',
        'can'   => 'gopanel.admins.roles.index',
    ],
    [
        'title' => 'Veb sayt'
    ],

    [
        'icon' => '<i class="fas fa-newspaper"></i>',
        'title' => 'Bloqlar',
        'route' => 'gopanel.blog.index',
        'can'   => 'gopanel.blog.index',
    ],

];
