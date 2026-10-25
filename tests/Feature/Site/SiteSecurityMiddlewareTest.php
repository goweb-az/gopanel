<?php

namespace Tests\Feature\Site;

use App\Http\Middleware\Security\RestrictDevDomain;
use App\Services\Site\SiteStatusService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Sayt tərəfi qlobal/route middleware-ləri: dev domen qoruyucusu, sayt
 * statusu (503), Server-Timing. Hamısı default SÖNÜKDÜR - əvvəlcə bu yoxlanır.
 *
 * Bazaya toxunulmur: test route-ları `web` qrupu olmadan qeydiyyatdan keçir,
 * sayt statusu isə saxta `SiteStatusService` ilə verilir.
 */
class SiteSecurityMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__sec-test', fn () => 'page');
        Route::get('/robots.txt', fn () => response("User-agent: *\nAllow: /\n"));
        Route::middleware('site.status')->group(function () {
            Route::get('/__status-test', fn () => 'site');
            Route::post('/__status-test', fn () => 'posted');
            Route::get('/llms.txt', fn () => 'llms');
        });
    }

    // ── Dev domen qoruyucusu ─────────────────────────────────────────────

    public function test_dev_guard_is_registered_globally_and_off_by_default(): void
    {
        $this->assertTrue(app(\App\Http\Kernel::class)->hasMiddleware(RestrictDevDomain::class));
        $this->assertFalse(config('custom.security.dev_guard.enabled'));

        config(['custom.security.dev_guard.domains' => 'dev.example.az']);

        $this->get('http://dev.example.az/__sec-test')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_guarded_host_redirects_strangers_and_hides_from_robots(): void
    {
        $this->enableDevGuard(['redirect_to' => 'https://example.az']);

        $this->get('http://dev.example.az/__sec-test?x=1')
            ->assertRedirect('https://example.az/__sec-test?x=1')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $robots = $this->get('http://dev.example.az/robots.txt')->assertOk();
        $this->assertStringContainsString('Disallow: /', $robots->getContent());

        // Başqa host-a toxunulmur
        $this->get('http://localhost/__sec-test')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_guarded_host_returns_403_without_redirect_target(): void
    {
        $this->enableDevGuard();

        $this->get('http://dev.example.az/__sec-test')->assertForbidden();
    }

    public function test_allowed_ip_sees_site_but_still_noindexed(): void
    {
        $this->enableDevGuard(['allowed_ips' => '203.0.113.0/24']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->get('http://dev.example.az/__sec-test')
            ->assertOk()
            ->assertSee('page')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_unlock_key_sets_hmac_cookie_and_off_clears_it(): void
    {
        $this->enableDevGuard(['unlock_key' => 's3cret', 'redirect_to' => 'https://example.az']);

        // Səhv açar → kənar adam kimi
        $this->get('http://dev.example.az/__sec-test?dev_key=wrong')->assertRedirect();

        $response = $this->get('http://dev.example.az/__sec-test?dev_key=s3cret')->assertOk();
        $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === RestrictDevDomain::UNLOCK_COOKIE);

        $this->assertNotNull($cookie);
        $this->assertNotSame('s3cret', $cookie->getValue(), 'Cookie-də açarın özü saxlanmamalıdır.');

        $this->withUnencryptedCookie(RestrictDevDomain::UNLOCK_COOKIE, $cookie->getValue())
            ->get('http://dev.example.az/__sec-test')
            ->assertOk();

        $this->get('http://dev.example.az/__sec-test?dev_key=off')->assertRedirect();
    }

    // ── Sayt statusu ─────────────────────────────────────────────────────

    public function test_site_status_is_not_enforced_by_default(): void
    {
        $this->fakeSiteStatus(false);
        $this->assertFalse(config('custom.security.site_status.enforce'));

        $this->get('/__status-test')->assertOk()->assertSee('site');
    }

    public function test_closed_site_returns_503_with_retry_after_when_enforced(): void
    {
        config(['custom.security.site_status.enforce' => true, 'custom.security.site_status.retry_after' => 600]);
        $this->fakeSiteStatus(false);

        $this->get('/__status-test')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '600')
            ->assertSee('<meta name="robots" content="noindex">', false);

        $this->post('/__status-test')->assertStatus(503);

        // İstisna siyahısı açıq qalır
        $this->get('/llms.txt')->assertOk();
    }

    public function test_open_site_passes_when_enforced(): void
    {
        config(['custom.security.site_status.enforce' => true]);
        $this->fakeSiteStatus(true);

        $this->get('/__status-test')->assertOk();
    }

    public function test_logged_in_admin_previews_closed_site(): void
    {
        config(['custom.security.site_status.enforce' => true]);
        $this->fakeSiteStatus(false);

        $admin = new \App\Models\Gopanel\Admin(['full_name' => 'Önizləmə', 'email' => 'p@example.com']);
        $admin->id = 999;

        $this->actingAs($admin, 'gopanel')->get('/__status-test')->assertOk()->assertSee('site');
        $this->assertTrue(View::shared('siteAdminPreview'));
    }

    public function test_site_status_service_treats_missing_values_as_open(): void
    {
        // Real oxu bazadan gəlir; burada yalnız «null/yoxdur = açıq» müqaviləsi yoxlanır
        $this->assertSame(
            ['site_enabled', 'login_enabled', 'register_enabled', 'payment_enabled'],
            array_keys($this->partialStatus(['site_status' => null])->toArray())
        );
        $this->assertTrue($this->partialStatus(['site_status' => null])->isSiteEnabled());
        $this->assertFalse($this->partialStatus(['site_status' => 0])->isSiteEnabled());
        $this->assertTrue($this->partialStatus([])->isLoginEnabled());
    }

    // ── Server-Timing ────────────────────────────────────────────────────

    public function test_server_timing_requires_both_flag_and_debug(): void
    {
        config(['custom.performance.server_timing.enabled' => false, 'app.debug' => true]);
        $this->get('/__sec-test')->assertHeaderMissing('Server-Timing');

        config(['custom.performance.server_timing.enabled' => true, 'app.debug' => false]);
        $this->get('/__sec-test')->assertHeaderMissing('Server-Timing');

        config(['custom.performance.server_timing.enabled' => true, 'app.debug' => true]);
        $header = $this->get('/__sec-test')->assertOk()->headers->get('Server-Timing');

        $this->assertMatchesRegularExpression('/^boot;dur=[\d.]+, app;dur=[\d.]+, db;dur=[\d.]+;desc="\d+ sorğu"$/u', (string) $header);
    }

    // ── Köməkçilər ───────────────────────────────────────────────────────

    /** @param array<string, mixed> $overrides */
    private function enableDevGuard(array $overrides = []): void
    {
        config(['custom.security.dev_guard' => array_merge([
            'enabled'         => true,
            'domains'         => 'dev.example.az',
            'allowed_ips'     => '',
            'unlock_key'      => '',
            'redirect_to'     => '',
            'redirect_status' => 302,
            'keep_path'       => true,
        ], $overrides)]);
    }

    private function fakeSiteStatus(bool $open): void
    {
        $this->app->instance(SiteStatusService::class, new class($open) extends SiteStatusService {
            public function __construct(private readonly bool $open)
            {
            }

            public function isSiteEnabled(): bool
            {
                return $this->open;
            }
        });
    }

    /** @param array<string, mixed> $row */
    private function partialStatus(array $row): SiteStatusService
    {
        $service = new SiteStatusService();
        $flags = [];
        foreach ($row as $column => $value) {
            $flags[$column] = $value === null ? true : (bool) $value;
        }
        (fn () => $this->flags = $flags)->call($service);

        return $service;
    }
}
