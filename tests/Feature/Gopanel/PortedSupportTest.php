<?php

namespace Tests\Feature\Gopanel;

use App\Enums\Common\PlatformEnum;
use App\Support\Geo\StaticMap;
use App\Support\Http\MobilePlatform;
use App\Support\Url\FrontendUrl;
use Tests\TestCase;

/**
 * Köçürülmüş kiçik köməkçilər: StaticMap, FrontendUrl, MobilePlatform, PlatformEnum.
 * Baza işlədilmir.
 */
class PortedSupportTest extends TestCase
{
    public function test_static_map_validates_point_and_falls_back_without_key(): void
    {
        config(['custom.map.static_map' => ['provider' => 'google', 'key' => '']]);

        $this->assertSame('none', StaticMap::provider(), 'Açarsız provayder `none`-a düşür.');
        $this->assertNull(StaticMap::image(40.4, 49.8));
        $this->assertSame('https://www.google.com/maps?q=40.4,49.8', StaticMap::link('40.4', '49.8'));
        $this->assertSame('40.400000, 49.800000', StaticMap::text(40.4, 49.8));

        $this->assertFalse(StaticMap::payload(0, 0)['has_location'], '0,0 doldurulmamış sahədir.');
        $this->assertNull(StaticMap::link(95, 10));
        $this->assertNull(StaticMap::link('abc', 10));

        config(['custom.map.static_map' => ['provider' => 'geoapify', 'key' => 'secret-key']]);
        $payload = StaticMap::payload(40.4, 49.8);

        $this->assertTrue($payload['has_location']);
        $this->assertStringStartsWith('https://maps.geoapify.com/v1/staticmap?', $payload['map_image']);
        $this->assertStringContainsString('apiKey=secret-key', $payload['map_image']);
    }

    public function test_frontend_url_uses_config_then_strips_api_subdomain(): void
    {
        config(['app.frontend_url' => 'https://shop.az/', 'app.url' => 'https://api.example.az']);
        $this->assertSame('https://shop.az', FrontendUrl::target());
        $this->assertSame('https://shop.az/az/bloq', FrontendUrl::to('/az/bloq/'));

        config(['app.frontend_url' => null]);
        $this->assertSame('https://example.az', FrontendUrl::target());

        config(['app.url' => 'http://gopanel.loc:8080']);
        $this->assertNull(FrontendUrl::target());
        $this->assertFalse(FrontendUrl::isConfigured());
        $this->assertSame('http://gopanel.loc:8080', FrontendUrl::base(), 'Ayrıca sayt yoxdursa APP_URL.');
    }

    public function test_mobile_platform_detection(): void
    {
        $this->assertSame(MobilePlatform::IOS, MobilePlatform::detect('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'));
        $this->assertSame(MobilePlatform::ANDROID, MobilePlatform::detect('Mozilla/5.0 (Linux; Android 14; Pixel 8)'));
        $this->assertSame(MobilePlatform::HUAWEI, MobilePlatform::detect('Mozilla/5.0 (Linux; Android 12; HUAWEI P50) HuaweiBrowser'));
        $this->assertNull(MobilePlatform::detect('Mozilla/5.0 (Windows NT 10.0; Win64; x64)'));
        $this->assertNull(MobilePlatform::detect(null));
    }

    public function test_platform_enum_visibility(): void
    {
        $this->assertSame(['mobile', 'both'], PlatformEnum::visibleOn(PlatformEnum::MOBILE));
        $this->assertSame(['website', 'mobile', 'both'], PlatformEnum::visibleOn(PlatformEnum::BOTH));
        $this->assertSame('Mobil tətbiq', PlatformEnum::MOBILE->label());
        $this->assertNull(PlatformEnum::tryFromValue('tv'));
        $this->assertCount(3, PlatformEnum::options());
    }
}
