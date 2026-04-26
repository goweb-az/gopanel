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
        'icon' => '<i class="bx bx-cog"></i>',
        'title' => 'Tənzimləmələr',
        'can'   => 'gopanel.settings.index',
        'inner' => [
            [
                'icon' => '<i class="bx bx-wrench"></i>',
                'title' => 'Sayt tənzimləmələri',
                'route' => 'gopanel.settings.site-settings.index',
                'can'   => 'gopanel.settings.site-settings.index',
            ],
            [
                'icon' => '<i class="bx bx-globe"></i>',
                'title' => 'Dillər',
                'route' => 'gopanel.settings.languages.index',
                'can'   => 'gopanel.settings.languages.index',
            ],
            [
                'icon' => '<i class="bx bx-transfer-alt"></i>',
                'title' => 'Tərcümələr',
                'route' => 'gopanel.settings.translations.index',
                'can'   => 'gopanel.settings.translations.index',
            ],
            [
                'icon' => '<i class="bx bx-menu"></i>',
                'title' => 'Menyu',
                'route' => 'gopanel.settings.menu.index',
                'can'   => 'gopanel.settings.menu.index',
            ],
        ],
    ],
    [
        'icon' => '<i class="bx bx-envelope"></i>',
        'title' => 'Əlaqə',
        'can'   => 'gopanel.contact.index',
        'inner' => [
            [
                'icon' => '<i class="bx bx-phone"></i>',
                'title' => 'Əlaqə məlumatları',
                'route' => 'gopanel.contact.contact-info.index',
                'can'   => 'gopanel.contact.contact-info.index',
            ],
            [
                'icon' => '<i class="bx bx-share-alt"></i>',
                'title' => 'Sosial şəbəkələr',
                'route' => 'gopanel.contact.socials.index',
                'can'   => 'gopanel.contact.socials.index',
            ],
        ],
    ],
    [
        'icon' => '<i class="bx bx-search-alt"></i>',
        'title' => 'SEO',
        'can'   => 'gopanel.seo.index',
        'inner' => [
            [
                'icon' => '<i class="bx bx-link"></i>',
                'title' => 'Yönləndirmələr',
                'route' => 'gopanel.seo.site-redirects.index',
                'can'   => 'gopanel.seo.site-redirects.index',
            ],
            [
                'icon' => '<i class="bx bx-code-alt"></i>',
                'title' => 'Analitik kodları',
                'route' => 'gopanel.seo.seo-analytics.index',
                'can'   => 'gopanel.seo.seo-analytics.index',
            ],
            [
                'icon' => '<i class="bx bx-bot"></i>',
                'title' => 'LLMs.txt',
                'route' => 'gopanel.seo.llms-txt.index',
                'can'   => 'gopanel.seo.llms-txt.index',
            ],
        ],
    ],
    [
        'title' => 'İdarəetmə'
    ],
    [
        'icon' => '<i class="bx bx-user"></i>',
        'title' => 'Adminlər',
        'route' => 'gopanel.admins.index',
        'can'   => 'gopanel.admins.index',
    ],
    [
        'icon' => '<i class="bx bx-shield"></i>',
        'title' => 'Vəzifələr',
        'route' => 'gopanel.admins.roles.index',
        'can'   => 'gopanel.admins.roles.index',
    ],
    [
        'title' => 'Veb sayt'
    ],
    [
        'icon' => '<i class="bx bx-news"></i>',
        'title' => 'Bloqlar',
        'route' => 'gopanel.blog.index',
        'can'   => 'gopanel.blog.index',
    ],
    [
        'icon' => '<i class="bx bx-images"></i>',
        'title' => 'Slayder',
        'route' => 'gopanel.slider.index',
        'can'   => 'gopanel.slider.index',
    ],
    [
        'title' => 'Monitorinq'
    ],
    [
        'icon' => '<i class="bx bx-history"></i>',
        'title' => 'Hərəkət tarixçəsi',
        'route' => 'gopanel.activity.activity-logs.index',
        'can'   => 'gopanel.activity.activity-logs.index',
    ],
    [
        'icon' => '<i class="bx bx-file"></i>',
        'title' => 'Loglar',
        'route' => 'gopanel.activity.file-logs.index',
        'can'   => 'gopanel.activity.file-logs.index',
    ],
    [
        'icon' => '<i class="bx bx-revision"></i>',
        'title' => 'Yeniləmələr',
        'route' => 'gopanel.system.updates.index',
        'can'   => 'gopanel.system.updates.index',
    ],

];
