<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Activity\LogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Performans diaqnostikası - standart `Server-Timing` başlığı.
 *
 * Niyə standart başlıq (qrgate-dəki `X-App-*` əvəzinə): brauzer onu
 * Network → Timing panelində qrafik kimi göstərir, əlavə alət lazım deyil.
 *
 *   Server-Timing: boot;dur=42, app;dur=118, db;dur=37;desc="14 sorğu"
 *
 * QLOBAL middleware-dir - boot vaxtını `LARAVEL_START`-dan ölçmək üçün ən
 * erkən nöqtədə olmalıdır. İşləməsi üçün İKİ şərt: `SERVER_TIMING_ENABLED=true`
 * VƏ `APP_DEBUG=true` (bax config/custom/performance.php). Sönük olanda
 * `DB::listen` belə qoşulmur - canlıda sıfır yük.
 *
 * `slow_ms > 0` olanda həddi aşan sorğu `single` jurnalına ən yavaş 8 SQL və
 * təkrarlanan şablonlarla (N+1 əlaməti) yazılır. DB-yə yazılmır: yavaş
 * sorğunun içində əlavə INSERT ölçünü təhrif edir.
 */
final class ServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('custom.performance.server_timing.enabled', false) || !config('app.debug')) {
            return $next($request);
        }

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = ['sql' => (string) $query->sql, 'time' => (float) $query->time];
        });

        $appStart = microtime(true);
        $response = $next($request);
        $now = microtime(true);

        $laravelStart = defined('LARAVEL_START') ? (float) LARAVEL_START : $appStart;
        $bootMs = round(($appStart - $laravelStart) * 1000, 1);
        $appMs = round(($now - $appStart) * 1000, 1);
        $dbMs = round(array_sum(array_column($queries, 'time')), 1);
        $count = count($queries);

        $response->headers->set(
            'Server-Timing',
            sprintf('boot;dur=%s, app;dur=%s, db;dur=%s;desc="%d sorğu"', $bootMs, $appMs, $dbMs, $count),
            false
        );

        $slowMs = (int) config('custom.performance.server_timing.slow_ms', 0);

        if ($slowMs > 0 && ($bootMs + $appMs) >= $slowMs) {
            $this->logSlow($request, $queries, $bootMs + $appMs, $dbMs);
        }

        return $response;
    }

    /**
     * @param  array<int, array{sql: string, time: float}>  $queries
     */
    private function logSlow(Request $request, array $queries, float $totalMs, float $dbMs): void
    {
        try {
            usort($queries, fn ($a, $b) => $b['time'] <=> $a['time']);

            $slowest = array_map(
                fn ($q) => round($q['time'], 1) . 'ms  ' . mb_substr((string) preg_replace('/\s+/', ' ', $q['sql']), 0, 200),
                array_slice($queries, 0, 8)
            );

            $groups = [];
            foreach ($queries as $q) {
                $norm = mb_substr((string) preg_replace('/\d+/', 'N', (string) preg_replace('/\s+/', ' ', $q['sql'])), 0, 120);
                $groups[$norm] = ($groups[$norm] ?? 0) + 1;
            }
            arsort($groups);

            $repeated = [];
            foreach ($groups as $sql => $n) {
                if ($n < 3) {
                    break;
                }
                $repeated[] = $n . 'x  ' . $sql;
            }

            LogService::channel('single', false)->warning('Yavaş sorğu', [
                'url'      => $request->fullUrl(),
                'total_ms' => $totalMs,
                'db_ms'    => $dbMs,
                'queries'  => count($queries),
                'slowest'  => $slowest,
                'repeated' => $repeated ?: 'N+1 yoxdur',
            ]);
        } catch (Throwable $e) {
            // Diaqnostika əsas cavabı heç vaxt sındırmamalıdır
            report($e);
        }
    }
}
