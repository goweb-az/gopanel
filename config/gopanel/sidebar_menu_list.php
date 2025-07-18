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
                'icon' => '<i class="fas fa-globe"></i>',
                'title' => 'Dillər',
                'route' => 'gopanel.languages.index',
                'can'   => 'gopanel.languages.index',
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
        'icon' => '<i class="fas fa-user-tie"></i>',
        'title' => 'Adminlər',
        'route' => 'gopanel.admins.index',
        'can'   => 'gopanel.admins.index',
    ],
    [
        'icon' => '<i class="fas fa-user-shield"></i>',
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

    [
        'icon' => '<i class="fas fa-images"></i>',
        'title' => 'Slayder',
        'route' => 'gopanel.slider.index',
        'can'   => 'gopanel.slider.index',
    ],


    [
        'title' => 'Aktivliklər'
    ],

    [
        'title' => 'Hərkət Tarixçəsi',
        'route' => 'gopanel.activity.history.index',
        'can'   => 'gopanel.activity.history.index',
        'icon'  => '<i class="bx bx-history"></i>'
    ],
    [
        'title' => 'Loglar',
        'route' => 'gopanel.activity.file-logs.index',
        'can'   => 'gopanel.activity.file-logs.index',
        'icon'  => '<i class="fas fa-laptop-medical"></i>'
    ],

];
