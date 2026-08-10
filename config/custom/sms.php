<?php

/*
|--------------------------------------------------------------------------
| SMS provayderi
|--------------------------------------------------------------------------
|
| `App\Services\Sms\SmsService` bu fayldan oxuyur.
|
| DEFAULT `LogProvider`-dir: heç nə göndərmir, yalnız `storage/logs/sms/`
| faylına yazır. Beləliklə yeni layihə/lokal mühit ilk gündən işləyir və
| heç kimə təsadüfi SMS getmir. Canlıda `.env` → SMS_PROVIDER dəyişdirilir.
|
*/

return [

    /*
     * `ProviderInterface` implement edən sinif.
     * Nümunə: App\Services\Sms\Providers\Lsim::class
     */
    'provider' => env('SMS_PROVIDER', \App\Services\Sms\Providers\LogProvider::class),

    /*
     * Ümumi açar. false → heç bir SMS getmir, hamısı `blocked` kimi jurnala düşür.
     * Test mühitində real nömrələrə göndərişin qarşısını almaq üçün.
     */
    'enabled' => (bool) env('SMS_ENABLED', true),

    /* Provayder API-sinə sorğu vaxtı (saniyə). */
    'timeout' => (int) env('SMS_TIMEOUT', 15),

    /*
     * LSIM (sendsms.az) - Azərbaycan operatorları.
     * Açarlar YALNIZ `.env`-dədir, koda yazılmır.
     */
    'lsim' => [
        'login'    => env('LSIM_LOGIN', ''),
        'password' => env('LSIM_PASSWORD', ''),
        'url'      => env('LSIM_URL', 'https://www.sendsms.az/smxml/api'),
        'title'    => env('LSIM_TITLE', env('APP_NAME', 'Gopanel')),
    ],

];
