<?php

namespace Tests\Feature\Gopanel;

use App\Models\Navigation\Menu;
use App\Services\Cache\CacheService;
use App\Support\Cache\CacheKey;
use Tests\TestCase;

/**
 * Keş qatı: versiyalı açarlar + model event-i ilə avtomatik invalidasiya.
 */
class CacheLayerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CacheKey::resetMemo();
    }

    public function test_model_map_points_to_real_classes(): void
    {
        $tracked = CacheKey::trackedModels();

        $this->assertContains(Menu::class, $tracked);

        foreach ($tracked as $class) {
            $this->assertTrue(class_exists($class), "{$class} mövcud deyil");
        }
    }

    public function test_keys_are_localized_and_versioned(): void
    {
        app()->setLocale('az');
        $az = CacheKey::menu('web', 'header');

        app()->setLocale('en');
        $en = CacheKey::menu('web', 'header');

        $this->assertNotSame($az, $en);
        $this->assertMatchesRegularExpression('/^menu\.v\d+\.web\.header\.en$/', $en);
    }

    public function test_flush_for_model_type_bumps_the_group_version(): void
    {
        $before = CacheKey::menu('web', 'header');

        CacheKey::flushForModelType(Menu::class);

        $this->assertNotSame($before, CacheKey::menu('web', 'header'));
    }

    public function test_model_event_flushes_group_through_provider(): void
    {
        $before = CacheKey::menu('web', 'footer');

        // Provider `saved` event-inə bağlanıb - DB-yə yazmadan event-i işə salırıq
        event('eloquent.saved: ' . Menu::class, new Menu());

        $this->assertNotSame($before, CacheKey::menu('web', 'footer'));
    }

    public function test_depends_on_invalidates_with_any_dependent_group(): void
    {
        $build  = fn () => CacheKey::dependsOn(CacheKey::content('web', ['id' => 5]), CacheKey::GROUP_CATEGORY);
        $before = $build();

        $this->assertStringContainsString('+category.v', $before);

        CacheKey::flushGroup(CacheKey::GROUP_CATEGORY);

        $this->assertNotSame($before, $build());
    }

    public function test_for_locale_changes_only_the_key_language(): void
    {
        app()->setLocale('az');

        $en = CacheKey::forLocale('en', fn () => CacheKey::menu('web', 'header'));

        $this->assertStringEndsWith('.en', $en);
        $this->assertSame('az', app()->getLocale());
        $this->assertStringEndsWith('.az', CacheKey::menu('web', 'header'));
        $this->assertStringEndsWith('.ru', CacheKey::languageSwitch('blog', 'slug', 'ru'));
        $this->assertStringNotContainsString('.az', CacheKey::languages());
    }

    public function test_remember_found_does_not_cache_null(): void
    {
        $key = 'test.remember-found.' . uniqid();

        $this->assertNull(CacheService::rememberFound($key, fn () => null));
        $this->assertFalse(CacheService::has($key));

        $this->assertSame('ok', CacheService::rememberFound($key, fn () => 'ok'));
        $this->assertSame('ok', CacheService::rememberFound($key, fn () => 'başqa'));
    }
}
