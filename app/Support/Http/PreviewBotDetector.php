<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Http\Request;

/**
 * Link önizləməsi üçün səhifəni ÇƏKƏN robotların tanınması.
 *
 * Problem: WhatsApp-da (Telegram, Facebook, Slack...) link göndəriləndə
 * messenger özü səhifəni açıb başlıq/şəkil götürür. Bu, insan açılışı DEYİL,
 * amma serverdə adi GET kimi görünür - ödəniş linki «açıldı» sayılırdı.
 *
 * ⚠️ Bu qat TAM həll deyil, YALNIZ birinci süzgəcdir:
 *  - robot öz adını gizlədə bilər (User-Agent saxtalaşdırıla bilər);
 *  - bəzi önizləmə xidmətləri adi brauzer kimi görünür.
 * Ona görə əsas təsdiq brauzerdə işləyən JS-dədir - robotlar JS icra etmir.
 *
 * Saf, vəziyyətsiz köməkçi (01-umumi.md §1).
 */
final class PreviewBotDetector
{
    /**
     * User-Agent içində axtarılan parçalar (kiçik hərflə).
     *
     * Siyahı MESSENGER önizləmələri ilə başlayır - bizim üçün ən vacib hissə
     * budur; sonra axtarış robotları və adi HTTP alətləri gəlir.
     */
    private const SIGNATURES = [
        // ── Messenger / sosial şəbəkə önizləmələri ──────────────
        'whatsapp'            => 'WhatsApp',
        'telegrambot'         => 'Telegram',
        'facebookexternalhit' => 'Facebook',
        'facebookcatalog'     => 'Facebook',
        'facebot'             => 'Facebook',
        'twitterbot'          => 'Twitter/X',
        'slackbot'            => 'Slack',
        'slack-imgproxy'      => 'Slack',
        'discordbot'          => 'Discord',
        'linkedinbot'         => 'LinkedIn',
        'skypeuripreview'     => 'Skype / Teams',
        'viber'               => 'Viber',
        'line-poker'          => 'LINE',
        'snapchat'            => 'Snapchat',
        'vkshare'             => 'VK',
        'redditbot'           => 'Reddit',
        'pinterest'           => 'Pinterest',
        'tumblr'              => 'Tumblr',
        'flipboard'           => 'Flipboard',
        'embedly'             => 'Embedly',
        'quora link preview'  => 'Quora',
        'nuzzel'              => 'Nuzzel',
        'outbrain'            => 'Outbrain',
        'bitlybot'            => 'Bitly',

        // ── Axtarış / indeksləmə robotları ──────────────────────
        'googlebot'            => 'Google',
        'google-inspectiontool' => 'Google',
        'apis-google'          => 'Google',
        'adsbot-google'        => 'Google',
        'feedfetcher-google'   => 'Google',
        'bingbot'              => 'Bing',
        'bingpreview'          => 'Bing',
        'applebot'             => 'Apple',
        'yandexbot'            => 'Yandex',
        'duckduckbot'          => 'DuckDuckGo',
        'baiduspider'          => 'Baidu',
        'ahrefsbot'            => 'Ahrefs',
        'semrushbot'           => 'SemRush',
        'petalbot'             => 'Petal',

        // ── Adi HTTP alətləri / avtomatlaşdırma ─────────────────
        'curl'                => 'curl',
        'wget'                => 'wget',
        'python-requests'     => 'Python',
        'python-urllib'       => 'Python',
        'go-http-client'      => 'Go',
        'okhttp'              => 'OkHttp',
        'axios'               => 'axios',
        'node-fetch'          => 'Node',
        'libwww-perl'         => 'Perl',
        'guzzlehttp'          => 'Guzzle',
        'postmanruntime'      => 'Postman',
        'headlesschrome'      => 'Headless brauzer',
        'phantomjs'           => 'PhantomJS',
        'puppeteer'           => 'Puppeteer',
        'playwright'          => 'Playwright',

        // ── Ümumi nişanlar (ən sonda - yanlış uyğunluq riski var) ─
        'crawler'             => 'Crawler',
        'spider'              => 'Crawler',
        'scraper'             => 'Scraper',
        'uptimerobot'         => 'Monitorinq',
        'pingdom'             => 'Monitorinq',
        'statuscake'          => 'Monitorinq',
        'preview'             => 'Önizləmə',
        'bot'                 => 'Robot',
    ];

    /** Səhifəni robot çəkir? User-Agent boşdursa da robot sayılır. */
    public static function isBot(?string $userAgent): bool
    {
        return self::label($userAgent) !== null;
    }

    /**
     * Robotun oxunaqlı adı: «WhatsApp», «Telegram»...
     * Robot deyilsə `null`.
     */
    public static function label(?string $userAgent): ?string
    {
        $userAgent = trim((string) $userAgent);

        // Brauzer HƏMİŞƏ User-Agent göndərir - boşdursa bu, insan deyil
        if ($userAgent === '') {
            return 'Naməlum (User-Agent yoxdur)';
        }

        $needle = mb_strtolower($userAgent);

        foreach (self::SIGNATURES as $signature => $label) {
            if (str_contains($needle, $signature)) {
                return $label;
            }
        }

        return null;
    }

    /**
     * Sorğu insan naviqasiyasına bənzəyirmi.
     *
     * Robot süzgəcindən ƏLAVƏ yoxlama: real brauzer səhifəyə keçəndə
     * `Sec-Fetch-Mode: navigate` göndərir və HTML qəbul etdiyini bildirir.
     * Önizləmə robotları adətən bunu göndərmir.
     *
     * ⚠️ Köhnə brauzerlər `Sec-Fetch-*` göndərmir - ona görə bu yoxlama
     *    BLOKLAMAQ üçün deyil, yalnız təsnifat üçün işlədilir.
     */
    public static function looksLikeNavigation(Request $request): bool
    {
        if (self::isBot($request->userAgent())) {
            return false;
        }

        // HEAD sorğusu ilə səhifəyə baxılmır - bu, yoxlama sorğusudur
        if ($request->isMethod('HEAD')) {
            return false;
        }

        $mode = (string) $request->header('Sec-Fetch-Mode', '');

        if ($mode !== '' && $mode !== 'navigate') {
            return false;
        }

        return str_contains((string) $request->header('Accept', ''), 'text/html');
    }
}
