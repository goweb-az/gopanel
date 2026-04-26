<?php

namespace Database\Seeders\mock;

use App\Models\Analytics\AnalyticsClick;
use App\Services\Site\Seo\AnalyticsService;
use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    public string $mockName = 'Analitika';

    /**
     * Seed analytics data using the real AnalyticsService.
     * This tests the full registration pipeline including GeoIP, UA parsing, etc.
     */
    public function run(): void
    {
        $service = app(AnalyticsService::class);

        $scenarios = [
            // Azerbaijan, Baku
            [
                'ip_address'      => '5.191.0.1',
                'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'accept_language' => 'az-AZ,az;q=0.9,en-US;q=0.8,en;q=0.7',
                'url'             => 'https://gopanel.loc/az/haqqimizda',
                'referer'         => 'https://google.com/search?q=proweb+agency',
            ],
            [
                'ip_address'      => '5.191.0.2',
                'user_agent'      => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                'accept_language' => 'az-AZ,az;q=0.9',
                'url'             => 'https://gopanel.loc/az',
                'referer'         => 'https://instagram.com/',
            ],
            // Turkey, Istanbul
            [
                'ip_address'      => '78.160.0.1',
                'user_agent'      => 'Mozilla/5.0 (Linux; Android 14; SM-A546B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
                'accept_language' => 'tr-TR,tr;q=0.9,en-US;q=0.8',
                'url'             => 'https://gopanel.loc/tr/hizmetler',
                'referer'         => 'https://google.com.tr/',
                'utm_source'      => 'google',
                'utm_medium'      => 'cpc',
                'utm_campaign'    => 'turkey_brand',
            ],
            [
                'ip_address'      => '78.160.0.2',
                'user_agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'accept_language' => 'tr-TR,tr;q=0.9',
                'url'             => 'https://gopanel.loc/tr',
                'referer'         => null,
            ],
            // USA, New York
            [
                'ip_address'      => '8.8.8.8',
                'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
                'accept_language' => 'en-US,en;q=0.9',
                'url'             => 'https://gopanel.loc/en/services',
                'referer'         => 'https://google.com/',
                'utm_source'      => 'google',
                'utm_medium'      => 'organic',
                'utm_campaign'    => 'brand_en',
            ],
            // Germany, Berlin
            [
                'ip_address'      => '80.81.0.1',
                'user_agent'      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'accept_language' => 'de-DE,de;q=0.9,en;q=0.8',
                'url'             => 'https://gopanel.loc/en/about',
                'referer'         => 'https://bing.com/',
            ],
            // UK, London
            [
                'ip_address'      => '81.2.69.142',
                'user_agent'      => 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0.6099.119 Mobile/15E148 Safari/604.1',
                'accept_language' => 'en-GB,en;q=0.9',
                'url'             => 'https://gopanel.loc/en/portfolio',
                'referer'         => 'https://google.co.uk/',
            ],
            // Russia, Moscow
            [
                'ip_address'      => '95.173.128.1',
                'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 YaBrowser/24.1.0.0 Safari/537.36',
                'accept_language' => 'ru-RU,ru;q=0.9',
                'url'             => 'https://gopanel.loc/ru',
                'referer'         => 'https://yandex.ru/',
            ],
            // UAE, Dubai
            [
                'ip_address'      => '94.200.0.1',
                'user_agent'      => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
                'accept_language' => 'ar-AE,ar;q=0.9,en;q=0.8',
                'url'             => 'https://gopanel.loc/en/contact',
                'referer'         => 'https://google.ae/',
                'ad_platform'     => 'Google Ads',
                'platform_data'   => ['gclid' => 'abc123def456'],
            ],
            // Georgia, Tbilisi
            [
                'ip_address'      => '31.146.0.1',
                'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
                'accept_language' => 'ka-GE,ka;q=0.9,en;q=0.8',
                'url'             => 'https://gopanel.loc/en/blog',
                'referer'         => 'https://facebook.com/',
                'utm_source'      => 'facebook',
                'utm_medium'      => 'social',
                'utm_campaign'    => 'georgia_awareness',
            ],
            // Extra AZ hits (for volume)
            [
                'ip_address'      => '5.191.0.3',
                'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0',
                'accept_language' => 'az-AZ,az;q=0.9',
                'url'             => 'https://gopanel.loc/az/bloq',
                'referer'         => 'https://google.az/',
            ],
            [
                'ip_address'      => '5.191.0.4',
                'user_agent'      => 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
                'accept_language' => 'az-AZ,az;q=0.9',
                'url'             => 'https://gopanel.loc/az/xidmetler',
                'referer'         => 'https://google.az/',
                'utm_source'      => 'google',
                'utm_medium'      => 'cpc',
                'utm_campaign'    => 'baku_services',
                'utm_term'        => 'web sayt sifarişi',
                'utm_content'     => 'ad_variant_a',
            ],
            // Extra TR hits
            [
                'ip_address'      => '78.160.0.3',
                'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'accept_language' => 'tr-TR,tr;q=0.9',
                'url'             => 'https://gopanel.loc/tr/iletisim',
                'referer'         => 'https://google.com.tr/',
            ],
        ];

        $extraScenarios = [
            [
                'ip_address'      => '8.8.4.4',
                'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 Edg/122.0.0.0',
                'accept_language' => 'en-US,en;q=0.9',
                'url'             => 'https://gopanel.loc/en/pricing',
                'referer'         => 'https://bing.com/search?q=gopanel+pricing',
                'utm_source'      => 'bing',
                'utm_medium'      => 'cpc',
                'utm_campaign'    => 'bing_pricing',
                'utm_content'     => 'headline_a',
                'ad_platform'     => 'Microsoft Bing Ads',
                'platform_data'   => ['msclkid' => 'msclkid-demo-001'],
            ],
            [
                'ip_address'      => '1.1.1.1',
                'user_agent'      => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/122.0.0.0 Mobile/15E148 Safari/604.1',
                'accept_language' => 'en-US,en;q=0.9',
                'url'             => 'https://gopanel.loc/en/demo',
                'referer'         => 'https://facebook.com/',
                'utm_source'      => 'facebook',
                'utm_medium'      => 'paid_social',
                'utm_campaign'    => 'demo_leads',
                'utm_content'     => 'story_video',
                'ad_platform'     => 'Facebook Ads',
                'platform_data'   => ['fbclid' => 'fbclid-demo-001'],
            ],
            [
                'ip_address'      => '9.9.9.9',
                'user_agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'accept_language' => 'en-US,en;q=0.9',
                'url'             => 'https://gopanel.loc/en/cases',
                'referer'         => 'https://linkedin.com/',
                'utm_source'      => 'linkedin',
                'utm_medium'      => 'sponsored',
                'utm_campaign'    => 'b2b_cases',
                'utm_content'     => 'carousel_1',
                'ad_platform'     => 'LinkedIn Ads',
                'platform_data'   => ['li_fat_id' => 'li-demo-001'],
            ],
            [
                'ip_address'      => '208.67.222.222',
                'user_agent'      => 'Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0 Mobile Safari/537.36',
                'accept_language' => 'ar-AE,ar;q=0.9,en;q=0.8',
                'url'             => 'https://gopanel.loc/en/contact',
                'referer'         => 'https://tiktok.com/',
                'utm_source'      => 'tiktok',
                'utm_medium'      => 'paid_social',
                'utm_campaign'    => 'uae_contact',
                'utm_content'     => 'short_video',
                'ad_platform'     => 'TikTok Ads',
                'platform_data'   => ['ttclid' => 'ttclid-demo-001'],
            ],
            [
                'ip_address'      => '185.199.108.153',
                'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 OPR/108.0.0.0',
                'accept_language' => 'de-DE,de;q=0.9,en;q=0.8',
                'url'             => 'https://gopanel.loc/en/blog/analytics-dashboard',
                'referer'         => 'https://x.com/',
                'utm_source'      => 'x',
                'utm_medium'      => 'social',
                'utm_campaign'    => 'blog_distribution',
                'utm_content'     => 'thread_analytics',
                'ad_platform'     => 'Twitter Ads',
                'platform_data'   => ['twclid' => 'twclid-demo-001'],
            ],
            [
                'ip_address'      => '18.64.0.1',
                'user_agent'      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'accept_language' => 'en-US,en;q=0.9',
                'url'             => 'https://gopanel.loc/en/services/seo',
                'referer'         => 'https://pinterest.com/',
                'utm_source'      => 'pinterest',
                'utm_medium'      => 'paid_social',
                'utm_campaign'    => 'seo_visuals',
                'utm_content'     => 'pin_board_a',
                'ad_platform'     => 'Pinterest Ads',
                'platform_data'   => ['epik' => 'epik-demo-001'],
            ],
            [
                'ip_address'      => '23.32.0.1',
                'user_agent'      => 'Mozilla/5.0 (iPad; CPU OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
                'accept_language' => 'en-GB,en;q=0.9',
                'url'             => 'https://gopanel.loc/en/portfolio/ecommerce',
                'referer'         => 'https://snapchat.com/',
                'utm_source'      => 'snapchat',
                'utm_medium'      => 'paid_social',
                'utm_campaign'    => 'portfolio_retarg',
                'utm_content'     => 'snap_story',
                'ad_platform'     => 'Snapchat Ads',
                'platform_data'   => ['scid' => 'scid-demo-001'],
            ],
            [
                'ip_address'      => '34.117.59.81',
                'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0',
                'accept_language' => 'tr-TR,tr;q=0.9',
                'url'             => 'https://gopanel.loc/tr/blog/google-ads',
                'referer'         => 'https://taboola.com/',
                'utm_source'      => 'taboola',
                'utm_medium'      => 'native',
                'utm_campaign'    => 'native_blog',
                'utm_content'     => 'widget_a',
                'ad_platform'     => 'Taboola Ads',
                'platform_data'   => ['tbclid' => 'tbclid-demo-001'],
            ],
            [
                'ip_address'      => '45.33.32.156',
                'user_agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
                'accept_language' => 'ru-RU,ru;q=0.9,en;q=0.8',
                'url'             => 'https://gopanel.loc/ru/uslugi',
                'referer'         => 'https://outbrain.com/',
                'utm_source'      => 'outbrain',
                'utm_medium'      => 'native',
                'utm_campaign'    => 'ru_services',
                'utm_content'     => 'recommendation_a',
                'ad_platform'     => 'Outbrain Ads',
                'platform_data'   => ['obclid' => 'obclid-demo-001'],
            ],
            [
                'ip_address'      => '77.88.8.8',
                'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 YaBrowser/24.1.0.0 Safari/537.36',
                'accept_language' => 'ru-RU,ru;q=0.9',
                'url'             => 'https://gopanel.loc/ru/kontakty',
                'referer'         => 'https://yandex.ru/',
                'utm_source'      => 'yandex',
                'utm_medium'      => 'cpc',
                'utm_campaign'    => 'ru_brand',
                'utm_content'     => 'search_ad_1',
                'ad_platform'     => 'Yandex Ads',
                'platform_data'   => ['yclid' => 'yclid-demo-001'],
            ],
        ];

        foreach ($extraScenarios as $scenario) {
            $scenarios[] = $scenario;
        }

        $volumePages = [
            ['url' => 'https://gopanel.loc/az', 'locale' => 'az-AZ,az;q=0.9', 'ip' => '5.191.0.10'],
            ['url' => 'https://gopanel.loc/az/xidmetler', 'locale' => 'az-AZ,az;q=0.9', 'ip' => '5.191.0.11'],
            ['url' => 'https://gopanel.loc/en/services', 'locale' => 'en-US,en;q=0.9', 'ip' => '8.8.8.8'],
            ['url' => 'https://gopanel.loc/en/contact', 'locale' => 'en-US,en;q=0.9', 'ip' => '1.1.1.1'],
            ['url' => 'https://gopanel.loc/tr/hizmetler', 'locale' => 'tr-TR,tr;q=0.9', 'ip' => '78.160.0.10'],
            ['url' => 'https://gopanel.loc/ru', 'locale' => 'ru-RU,ru;q=0.9', 'ip' => '95.173.128.10'],
        ];

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
        ];

        for ($i = 0; $i < 24; $i++) {
            $page = $volumePages[$i % count($volumePages)];
            $scenarios[] = [
                'ip_address'      => $page['ip'],
                'user_agent'      => $userAgents[$i % count($userAgents)],
                'accept_language' => $page['locale'],
                'url'             => $page['url'],
                'referer'         => $i % 3 === 0 ? 'https://google.com/search?q=gopanel' : 'https://gopanel.loc/ref/demo-' . $i,
                'utm_source'      => $i % 4 === 0 ? 'google' : null,
                'utm_medium'      => $i % 4 === 0 ? 'remarketing' : null,
                'utm_campaign'    => $i % 4 === 0 ? 'volume_seed' : null,
                'utm_content'     => $i % 4 === 0 ? 'banner_' . ($i + 1) : null,
            ];
        }

        $this->command->info('Analytics Seeder: ' . count($scenarios) . ' click qeyd edilir...');

        foreach ($scenarios as $i => $data) {
            try {
                $existsQuery = AnalyticsClick::where('ip_address', $data['ip_address'])
                    ->where('url', $data['url']);

                if (array_key_exists('referer', $data) && $data['referer'] === null) {
                    $existsQuery->whereNull('referer');
                } else {
                    $existsQuery->where('referer', $data['referer'] ?? null);
                }

                if ($existsQuery->exists()) {
                    $this->command->line('  [' . ($i + 1) . '] - movcuddur | ' . ($data['url'] ?? '-'));
                    continue;
                }

                $click = $service->register($data);
                $country = $click->country ? $click->country->name : 'Unknown';
                $device  = $click->device  ? $click->device->device_type : 'Unknown';
                $this->command->line(
                    '  [' . ($i + 1) . '] ✓ ' . $country . ' | ' . $device . ' | ' . ($data['url'] ?? '-')
                );
            } catch (\Throwable $e) {
                $this->command->error('  [' . ($i + 1) . '] ✗ ' . $e->getMessage());
            }
        }

        $this->command->info('Analytics Seeder tamamlandı.');
    }
}
