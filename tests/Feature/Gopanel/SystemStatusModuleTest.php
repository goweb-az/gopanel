<?php

namespace Tests\Feature\Gopanel;

use App\Helpers\Gopanel\ServerMetricsHelper;
use App\Services\Gopanel\System\SystemStatusService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SystemStatusModuleTest extends TestCase
{
    public function test_system_status_routes_are_declared(): void
    {
        $this->assertTrue(Route::has('gopanel.system-status.index'));
        $this->assertTrue(Route::has('gopanel.system-status.data'));
    }

    public function test_permission_and_sidebar_are_registered(): void
    {
        $names = collect(config('gopanel.permission_list.gopanel.Sistem vəziyyəti'))->pluck('name')->all();

        $this->assertContains('gopanel.system-status.index', $names);

        $sidebar = collect(config('gopanel.sidebar_menu_list'));
        $this->assertTrue($sidebar->contains(fn ($item) => ($item['route'] ?? null) === 'gopanel.system-status.index'));
    }

    public function test_snapshot_returns_every_block_the_view_needs(): void
    {
        $snapshot = app(SystemStatusService::class)->snapshot();

        foreach ([
            'cpu', 'memory', 'disk', 'php', 'queue', 'pending', 'failed',
            'scheduler', 'storage', 'backup', 'database', 'server', 'checked_at',
        ] as $key) {
            $this->assertArrayHasKey($key, $snapshot, "snapshot-da «{$key}» yoxdur");
        }

        // Ölçü göstəriciləri blade-ə HAZIR mətn kimi gedir (01-umumi.md § 3)
        foreach (['cpu', 'memory', 'disk'] as $metric) {
            $this->assertArrayHasKey('percent_text', $snapshot[$metric]);
            $this->assertArrayHasKey('tone', $snapshot[$metric]);
            $this->assertArrayHasKey('rows', $snapshot[$metric]);
        }
    }

    public function test_gauges_only_expose_numbers_and_tones(): void
    {
        $service  = app(SystemStatusService::class);
        $snapshot = $service->snapshot();
        $gauges   = $service->gauges($snapshot);

        $this->assertSame(['cpu', 'memory', 'disk'], array_keys($gauges));

        foreach ($gauges as $gauge) {
            $this->assertSame(['value', 'tone'], array_keys($gauge));
            $this->assertContains($gauge['tone'], ['success', 'warning', 'danger', 'secondary']);
        }
    }

    public function test_sync_queue_driver_is_reported_as_a_warning(): void
    {
        // Testlərdə `QUEUE_CONNECTION=sync`-dir (bax phpunit.xml) - bu halda
        // istifadəçi xəbərdar edilməlidir, çünki backup kimi ağır işlər
        // sorğunun içində icra olunur.
        config(['queue.default' => 'sync']);

        $queue = app(SystemStatusService::class)->snapshot()['queue'];

        $this->assertSame('sync', $queue['driver']);
        $this->assertFalse($queue['supported']);
        $this->assertStringContainsString('sync', (string) $queue['warning']);
    }

    public function test_scheduler_state_is_derived_from_the_heartbeat_file(): void
    {
        $scheduler = app(SystemStatusService::class)->snapshot()['scheduler'];

        $this->assertArrayHasKey('alive', $scheduler);
        $this->assertContains($scheduler['state_text'], ['İşləyir', 'Dayanıb', 'Heç vaxt işləməyib']);

        // Heartbeat KEŞDƏ deyil, faylda saxlanılır - `cache:clear` göstəricini
        // sıfırlamamalıdır
        $this->assertStringContainsString('storage', (string) config('gopanel.system_status.heartbeat_file'));

        $kernel = file_get_contents(base_path('app/Console/Kernel.php'));
        $this->assertStringContainsString('system_status.heartbeat_file', $kernel);
        $this->assertStringContainsString('everyMinute()', $kernel);
    }

    public function test_thresholds_drive_the_colour_not_the_javascript(): void
    {
        $thresholds = (array) config('gopanel.system_status.thresholds');

        $this->assertSame(75, $thresholds['warning']);
        $this->assertSame(90, $thresholds['danger']);

        // JS yalnız ton adını rəngə çevirir - hədd hesablaması orada olmamalıdır
        $js = file_get_contents(base_path('public/assets/gopanel/js/modules/system-status.js'));
        $this->assertStringContainsString('TONE_COLORS', $js);
        $this->assertStringNotContainsString('> 90', $js);
        $this->assertStringNotContainsString('> 75', $js);
    }

    public function test_server_metrics_helper_degrades_gracefully(): void
    {
        $metrics = app(ServerMetricsHelper::class);

        // Alınmayan göstərici xəta atmır, `null` qaytarır
        $this->assertNull($metrics->duration(null));
        $this->assertSame('5 san.', $metrics->duration(5));
        $this->assertSame('2 dəq. 5 san.', $metrics->duration(125));

        $disk = $metrics->disk();
        $this->assertArrayHasKey('supported', $disk);
        $this->assertArrayHasKey('percent', $disk);
    }
}
