<?php

namespace Tests\Feature\Gopanel;

use App\Http\Middleware\Gopanel\GopanelHostMiddleware;
use App\Http\Responses\Api\ErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Http\Responses\Api\SuccessResponse;
use App\Support\Api\PaginationParams;
use App\Support\Date\FilterDateRange;
use App\Support\Date\LocalizedDateFormatter;
use App\Support\Gopanel\StatCard;
use App\Support\Url\GopanelUrl;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Törəmə layihələrdən köçürülmüş ümumi qat - config/app konteksti tələb edənlər.
 */
class SharedLayerTest extends TestCase
{
    public function test_api_success_envelope_matches_contract(): void
    {
        $json = SuccessResponse::make(null, 'Hazır')->getData(true);

        $this->assertTrue($json['success']);
        $this->assertSame('Hazır', $json['message']);
        $this->assertSame([], $json['data'], 'data null olmamalıdır - {} qaytarılır');
    }

    public function test_api_paginator_goes_to_meta(): void
    {
        $paginator = new LengthAwarePaginator([['id' => 1]], 45, 20, 2);
        $json      = SuccessResponse::make($paginator)->getData(true);

        $this->assertSame([['id' => 1]], $json['data']);
        $this->assertSame(['current_page' => 2, 'per_page' => 20, 'total' => 45, 'last_page' => 3], $json['meta']['pagination']);
    }

    public function test_api_error_envelope_has_stable_code(): void
    {
        $response = ErrorResponse::make('Tapılmadı', 404);
        $json     = $response->getData(true);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($json['success']);
        $this->assertSame('RESOURCE_NOT_FOUND', $json['code']);

        $validation = ErrorResponse::validation(['a' => ['Ad mütləqdir.'], 'b' => ['Ad mütləqdir.', 'Şəkil səhvdir.']])->getData(true);
        $this->assertSame('VALIDATION_ERROR', $validation['code']);
        $this->assertStringContainsString('1. Ad mütləqdir. 2. Şəkil səhvdir.', $validation['message']);
    }

    public function test_api_routes_render_envelope_on_errors(): void
    {
        Route::get('api/__test/not-found', fn () => abort(404))->middleware('api');

        $this->getJson('api/__test/not-found')
            ->assertStatus(404)
            ->assertJson(['success' => false, 'code' => 'RESOURCE_NOT_FOUND']);
    }

    public function test_pagination_params_are_clamped(): void
    {
        $params = PaginationParams::make('100000', '-3');

        $this->assertSame(100, $params->perPage);
        $this->assertSame(1, $params->page);
        $this->assertSame(20, PaginationParams::make()->perPage);
    }

    public function test_filter_date_range_parses_flatpickr_value(): void
    {
        $this->assertSame(['2026-09-01', '2026-09-30'], FilterDateRange::parse('2026-09-01+-+2026-09-30'));
        $this->assertSame([null, null], FilterDateRange::parse('pozulmuş'));
        $this->assertSame('01.09.2026 - 30.09.2026', FilterDateRange::label('2026-09-01', '2026-09-30'));
    }

    public function test_localized_date_formatter_does_not_touch_global_locale(): void
    {
        $this->assertSame('27 sentyabr 2026', LocalizedDateFormatter::date('2026-09-27', 'az'));
        $this->assertSame('27 September 2026', LocalizedDateFormatter::date('2026-09-27', 'en'));
        $this->assertNull(LocalizedDateFormatter::date(null));
    }

    public function test_stat_card_keeps_old_shape_and_supports_delta(): void
    {
        $old = StatCard::make(['label' => 'A', 'value' => 10, 'current' => 10, 'previous' => 5]);
        $this->assertSame('+100%', $old['trend']['percent']);
        $this->assertSame('fas fa-chart-bar', $old['icon']);

        $delta = StatCard::make(['label' => 'B', 'value' => 12.5, 'format' => 'percent', 'delta' => -3.2]);
        $this->assertSame('down', $delta['trend']['direction']);
        $this->assertStringEndsWith('%', $delta['value']);
    }

    public function test_gopanel_url_falls_back_to_app_url(): void
    {
        config(['app.gopanel_url' => null, 'app.url' => 'http://site.test']);
        $this->assertSame('http://site.test/gopanel/x', GopanelUrl::to('gopanel/x'));

        config(['app.gopanel_url' => 'https://go.site.test/']);
        $this->assertSame('https://go.site.test', GopanelUrl::base());
        $this->assertNull(GopanelUrl::route('movcud.olmayan.route'));
    }

    public function test_gopanel_host_middleware_blocks_foreign_host_only_when_configured(): void
    {
        // Middleware birbaşa çağırılır: `web` qrupundakı HumanGate (bazadakı ayara görə)
        // sorğunu daha əvvəl tuta bilər - bu test yalnız host qaydasını yoxlayır.
        $middleware = new GopanelHostMiddleware();
        $next       = fn () => new Response('ok');

        config(['app.gopanel_url' => null]);
        $this->assertSame('ok', $middleware->handle(Request::create('http://other.test/gopanel'), $next)->getContent());

        config(['app.gopanel_url' => 'https://go.site.test']);
        $this->assertSame('ok', $middleware->handle(Request::create('https://go.site.test/gopanel'), $next)->getContent());

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle(Request::create('http://other.test/gopanel'), $next);
    }

    public function test_gopanel_route_group_uses_host_middleware(): void
    {
        $route = Route::getRoutes()->getByName('gopanel.auth.login');

        $this->assertContains('gopanel.host', $route->gatherMiddleware());
    }
}
