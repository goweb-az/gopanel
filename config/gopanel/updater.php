<?php

return [
    'enabled' => env('GOPANEL_UPDATER_ENABLED', true),

    'github' => [
        'owner'  => env('GOPANEL_GITHUB_OWNER', 'goweb-az'),
        'repo'   => env('GOPANEL_GITHUB_REPO', 'gopanel'),
        'branch' => env('GOPANEL_GITHUB_BRANCH', 'master'),
        'token'  => env('GOPANEL_GITHUB_TOKEN', null),
    ],

    'backup_path'  => storage_path('app/gopanel-backups'),
    'version_file' => base_path('gopanel_version.json'),
    'manifest'     => 'gopanel_updates.json',
];
