<?php

/*
|--------------------------------------------------------------------------
| İzlənən növbələr (Job Monitor)
|--------------------------------------------------------------------------
|
| `App\Services\Queue\QueueMonitorService` bu siyahını gəzir və hər növbə üçün
| gözləyən / gecikmiş / işlənən sayını göstərir.
|
| Açar = növbənin ADI (`dispatch()->onQueue('email_queue')` ilə eyni).
| `connection` verilməsə `config('queue.default')` işlədilir.
|
| Redis və database driver-ləri dəstəklənir; `sync` üçün say həmişə 0-dır
| (sinxron növbə heç nə saxlamır).
|
| Worker nümunəsi:
|   php artisan queue:work --queue=email_queue,notification_queue,default
|
*/

return [

    'queues' => [

        'default' => [
            'label'   => 'Ümumi (default)',
            'channel' => 'database',
        ],

        'email_queue' => [
            'label'   => 'E-poçt',
            'channel' => 'mail',
        ],

        // 'notification_queue' => [
        //     'label'      => 'Bildirişlər',
        //     'channel'    => 'notification',
        //     'connection' => 'redis',
        // ],
    ],

    /* Monitor panelində göstərilən son uğursuz iş sayı. */
    'recent_failed_limit' => 15,

];
