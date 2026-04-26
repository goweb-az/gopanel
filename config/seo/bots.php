<?php

/*
|--------------------------------------------------------------------------
| Bot Bloklaması Konfiqurasiyası
|--------------------------------------------------------------------------
|
| Bu fayl HumanGate middleware-i tərəfindən istifadə olunan "yaxşı bot"
| siyahısını təyin edir. HumanGate aktivləşdirildikdə, sayta daxil olan
| hər istifadəçidən JS vasitəsilə cookie set etməsi tələb olunur.
| Bot-lar JS icra edə bilmir, ona görə bu addımda bloklanırlar.
|
| İSTİFADƏ QAYDALARI:
|
| 1. AKTİVLƏŞDİRMƏ:
|    - Gopanel > Tənzimləmələr > Sayt Tənzimləmələri bölməsindən
|      "Bot Bloklaması" toggle-ını aktiv edin.
|    - Kernel.php-də 'web' middleware qrupuna HumanGate artıq əlavə olunub.
|
| 2. YAXŞI BOTLAR:
|    - Aşağıdakı 'good' array-ində sadalanan bot-lar bloklanMIR.
|    - Bunlar SEO üçün vacib axtarış motoru və sosial media bot-larıdır.
|    - Yeni "yaxşı bot" əlavə etmək: User-Agent string-indəki identifikatoru
|      array-ə əlavə edin (case-insensitive yoxlanılır).
|
| 3. MEXANIZM:
|    - GET + text/html request gəldikdə '__hs' cookie yoxlanılır.
|    - Cookie yoxdursa, JS ilə cookie set edib reload edən səhifə göstərilir.
|    - Bot-lar JS icra edə bilmir → sonsuz redirect loop-da qalır.
|    - API, storage, assets path-ləri avtomatik olaraq keçirilir.
|
| 4. DİQQƏT:
|    - User-Agent saxtalaşdırıla bilər, ona görə bu tam təhlükəsizlik
|      həlli deyil, amma əksər surətçıxaran bot-ları dayandırır.
|    - CloudFlare/Captcha kimi xidmətlər daha güclü alternativlərdir.
|
*/

return [

    /*
    * İcazə verilən "yaxşı botlar"
    * Middleware bunları yoxlayır, uyğun gələrsə, bloklanmır.
    */
    'good' => [
        // Axtarış matorları
        'Googlebot',
        'Googlebot-Image',
        'Googlebot-News',
        'Googlebot-Video',
        'Bingbot',
        'Slurp',              // Yahoo
        'DuckDuckBot',
        'Baiduspider',
        'YandexBot',
        'Sogou',
        'Exabot',
        'ia_archiver',        // Alexa

        // Sosial mediaya baxış
        'Twitterbot',
        'LinkedInBot',
        'Slackbot',
        'WhatsApp',
        'TelegramBot',
        'Discordbot',
        'Pinterestbot',
        'FacebookExternalHit',
        'Instagram',

        // Apple / Microsoft
        'Applebot',
        'msnbot',
    ],

];
