<?php

declare(strict_types=1);

namespace App\Helpers\Gopanel;

use App\Services\Cache\CacheService;
use App\Support\Files\ByteSize;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Serverin əməliyyat sistemi səviyyəsindəki göstəriciləri: CPU, yaddaş,
 * disk, işləmə müddəti.
 *
 * İki mühit dəstəklənir:
 *   - Linux (prod)  — `/proc` faylları oxunur, əlavə proses işə salınmır.
 *   - Windows (dev) — `wmic` çağırılır; bəzi göstəricilər mövcud olmaya bilər.
 *
 * Göstərici alınmadıqda `null` qaytarılır — səhifə «—» göstərir, xəta atmır.
 * Monitor səhifəsi bir neçə saniyədən bir çağırıldığı üçün burada ağır
 * əməliyyat (rekursiv qovluq gəzmə, uzun sorğu) YOXDUR.
 */
class ServerMetricsHelper
{
    /**
     * CPU ölçməsinin əvvəlki nümunəsi burada saxlanılır.
     *
     * `/proc/stat` mütləq deyil, ARTAN sayğaclar verir — faiz yalnız iki
     * ölçmənin fərqindən çıxır. Əvvəlki nümunəni keşdə saxlamaqla sorğunun
     * içində gözləmə (`usleep`) etməyə ehtiyac qalmır: fərq iki yenilənmə
     * arasındakı real aralığa görə hesablanır.
     */
    private const CPU_SAMPLE_KEY = 'gopanel.system-status.cpu-sample';

    /** Keşdə nümunə yoxdursa yerində bu qədər gözləyib ikinci ölçmə alınır. */
    private const CPU_INLINE_SAMPLE_US = 150000;

    /** Keşdəki nümunə bundan köhnədirsə etibarsız sayılır (saniyə). */
    private const CPU_SAMPLE_MAX_AGE = 120;

    public function isLinux(): bool
    {
        return PHP_OS_FAMILY === 'Linux' && is_readable('/proc');
    }

    /**
     * CPU göstəriciləri.
     *
     * @return array{supported: bool, usage: float|null, cores: int|null,
     *               model: string|null, load: array<string, float>|null,
     *               load_percent: float|null, note: string|null}
     */
    public function cpu(): array
    {
        $cores = $this->cores();
        $load  = $this->loadAverage();

        $loadPercent = ($load && $cores)
            ? round(min(($load['1'] / $cores) * 100, 100), 1)
            : null;

        return [
            'supported'    => $this->isLinux() || PHP_OS_FAMILY === 'Windows',
            'usage'        => $this->cpuUsage(),
            'cores'        => $cores,
            'model'        => $this->cpuModel(),
            'load'         => $load,
            'load_percent' => $loadPercent,
            'note'         => $this->isLinux() ? null : 'Bəzi göstəricilər yalnız Linux serverdə mövcuddur.',
        ];
    }

    /**
     * Fiziki yaddaş (RAM) və swap.
     *
     * @return array{supported: bool, total: int|null, used: int|null,
     *               free: int|null, percent: float|null,
     *               swap_total: int|null, swap_used: int|null}
     */
    public function memory(): array
    {
        $data = $this->isLinux() ? $this->linuxMemory() : $this->windowsMemory();

        $total = $data['total'] ?? null;
        $free  = $data['free'] ?? null;
        $used  = ($total !== null && $free !== null) ? max($total - $free, 0) : null;

        return [
            'supported'  => $total !== null,
            'total'      => $total,
            'used'       => $used,
            'free'       => $free,
            'percent'    => ($total && $used !== null) ? round(($used / $total) * 100, 1) : null,
            'swap_total' => $data['swap_total'] ?? null,
            'swap_used'  => $data['swap_used'] ?? null,
        ];
    }

    /**
     * Layihənin yerləşdiyi diskin doluluğu.
     *
     * @return array{supported: bool, total: int|null, used: int|null,
     *               free: int|null, percent: float|null, path: string}
     */
    public function disk(?string $path = null): array
    {
        $path  = $path ?: base_path();
        $total = @disk_total_space($path);
        $free  = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return [
                'supported' => false,
                'total'     => null,
                'used'      => null,
                'free'      => null,
                'percent'   => null,
                'path'      => $path,
            ];
        }

        $used = (int) ($total - $free);

        return [
            'supported' => true,
            'total'     => (int) $total,
            'used'      => $used,
            'free'      => (int) $free,
            'percent'   => round(($used / $total) * 100, 1),
            'path'      => $path,
        ];
    }

    /** Serverin işə düşməsindən keçən saniyə (Linux). */
    public function uptimeSeconds(): ?int
    {
        if (!$this->isLinux()) {
            return null;
        }

        $raw = @file_get_contents('/proc/uptime');

        if (!is_string($raw) || $raw === '') {
            return null;
        }

        return (int) (float) strtok($raw, ' ');
    }

    /**
     * Bayt dəyərini oxunaqlı mətnə çevirir: 5.4 GB, 12 MB, 340 KB.
     *
     * Format `App\Support\Files\ByteSize`-dədir — burada yalnız köhnə
     * çağırışlar üçün ötürücü metod saxlanılır.
     */
    public function bytes(int $bytes): string
    {
        return ByteSize::human($bytes);
    }

    /** Saniyəni «12 gün 3 saat» şəklində yazır. */
    public function duration(?int $seconds): ?string
    {
        if ($seconds === null || $seconds < 0) {
            return null;
        }

        $days    = intdiv($seconds, 86400);
        $hours   = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return $days . ' gün ' . $hours . ' saat';
        }

        if ($hours > 0) {
            return $hours . ' saat ' . $minutes . ' dəq.';
        }

        if ($minutes > 0) {
            return $minutes . ' dəq. ' . ($seconds % 60) . ' san.';
        }

        return $seconds . ' san.';
    }

    // ── CPU ─────────────────────────────────────────────────────────────

    /**
     * Anlıq CPU istifadəsi (%).
     *
     * Linux-da `/proc/stat`-ın iki ölçməsinin fərqindən hesablanır.
     * Windows-da `wmic` çağırılır (dev mühiti üçün — bir qədər yavaşdır).
     */
    private function cpuUsage(): ?float
    {
        if ($this->isLinux()) {
            return $this->linuxCpuUsage();
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $value = $this->wmicValue('cpu get loadpercentage', 'LoadPercentage');

            return $value === null ? null : round((float) $value, 1);
        }

        return null;
    }

    private function linuxCpuUsage(): ?float
    {
        $current = $this->readProcStat();

        if ($current === null) {
            return null;
        }

        $previous = CacheService::get(self::CPU_SAMPLE_KEY);

        // Keşdə yararlı nümunə yoxdursa yerində qısa ikinci ölçmə alınır.
        if (!is_array($previous)
            || !isset($previous['total'], $previous['idle'], $previous['time'])
            || (time() - (int) $previous['time']) > self::CPU_SAMPLE_MAX_AGE
            || $current['total'] <= $previous['total']
        ) {
            $previous = $current;
            usleep(self::CPU_INLINE_SAMPLE_US);
            $current = $this->readProcStat();

            if ($current === null) {
                return null;
            }
        }

        CacheService::put(self::CPU_SAMPLE_KEY, $current + ['time' => time()], 300);

        $totalDelta = $current['total'] - $previous['total'];
        $idleDelta  = $current['idle'] - $previous['idle'];

        if ($totalDelta <= 0) {
            return null;
        }

        return round(max(0, min(100, (1 - $idleDelta / $totalDelta) * 100)), 1);
    }

    /**
     * `/proc/stat`-ın birinci sətri: cpu user nice system idle iowait ...
     *
     * @return array{total: float, idle: float}|null
     */
    private function readProcStat(): ?array
    {
        $handle = @fopen('/proc/stat', 'r');

        if ($handle === false) {
            return null;
        }

        $line = fgets($handle);
        fclose($handle);

        if (!is_string($line) || !str_starts_with($line, 'cpu ')) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($line)) ?: [];
        array_shift($parts);   // «cpu» sözü

        $values = array_map('floatval', $parts);

        if (count($values) < 5) {
            return null;
        }

        // idle + iowait — prosessorun boş qaldığı vaxt
        return [
            'total' => array_sum($values),
            'idle'  => $values[3] + $values[4],
        ];
    }

    private function cores(): ?int
    {
        if ($this->isLinux()) {
            $raw = @file_get_contents('/proc/cpuinfo');

            if (is_string($raw)) {
                $count = preg_match_all('/^processor\s*:/mi', $raw);

                if ($count > 0) {
                    return $count;
                }
            }
        }

        $env = getenv('NUMBER_OF_PROCESSORS');

        return is_numeric($env) ? (int) $env : null;
    }

    private function cpuModel(): ?string
    {
        if (!$this->isLinux()) {
            return null;
        }

        $raw = @file_get_contents('/proc/cpuinfo');

        if (!is_string($raw)) {
            return null;
        }

        return preg_match('/^model name\s*:\s*(.+)$/mi', $raw, $m) ? trim($m[1]) : null;
    }

    /** @return array<string, float>|null */
    private function loadAverage(): ?array
    {
        if (!function_exists('sys_getloadavg')) {
            return null;
        }

        $load = @sys_getloadavg();

        if (!is_array($load) || count($load) < 3) {
            return null;
        }

        return [
            '1'  => round((float) $load[0], 2),
            '5'  => round((float) $load[1], 2),
            '15' => round((float) $load[2], 2),
        ];
    }

    // ── Yaddaş ──────────────────────────────────────────────────────────

    /** @return array<string, int|null> */
    private function linuxMemory(): array
    {
        $raw = @file_get_contents('/proc/meminfo');

        if (!is_string($raw)) {
            return [];
        }

        $read = static function (string $key) use ($raw): ?int {
            // Dəyər kilobaytdadır: «MemTotal:       16316136 kB»
            return preg_match('/^' . $key . ':\s+(\d+)\s*kB/mi', $raw, $m)
                ? (int) $m[1] * 1024
                : null;
        };

        $swapTotal = $read('SwapTotal');
        $swapFree  = $read('SwapFree');

        return [
            'total'      => $read('MemTotal'),
            // MemAvailable nüvənin öz hesabıdır — keşi nəzərə alır, ona görə
            // MemFree-dən qat-qat düzgündür.
            'free'       => $read('MemAvailable') ?? $read('MemFree'),
            'swap_total' => $swapTotal,
            'swap_used'  => ($swapTotal !== null && $swapFree !== null) ? max($swapTotal - $swapFree, 0) : null,
        ];
    }

    /** @return array<string, int|null> */
    private function windowsMemory(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        $output = $this->runProcess(['wmic', 'OS', 'get', 'FreePhysicalMemory,TotalVisibleMemorySize', '/value']);

        if ($output === null) {
            return [];
        }

        $read = static function (string $key) use ($output): ?int {
            return preg_match('/^' . $key . '=(\d+)/mi', $output, $m) ? (int) $m[1] * 1024 : null;
        };

        return [
            'total' => $read('TotalVisibleMemorySize'),
            'free'  => $read('FreePhysicalMemory'),
        ];
    }

    // ── Köməkçi ─────────────────────────────────────────────────────────

    private function wmicValue(string $query, string $key): ?string
    {
        $output = $this->runProcess(array_merge(['wmic'], explode(' ', $query), ['/value']));

        if ($output === null) {
            return null;
        }

        return preg_match('/^' . $key . '=(.+)$/mi', $output, $m) ? trim($m[1]) : null;
    }

    /**
     * Kənar proses işə salır. Xəta və ya gecikmə halında `null` qaytarır —
     * monitor səhifəsi bir göstəriciyə görə tamamilə sınmamalıdır.
     *
     * @param  list<string>  $command
     */
    private function runProcess(array $command, float $timeout = 5.0): ?string
    {
        try {
            $process = new Process($command);
            $process->setTimeout($timeout);
            $process->run();

            return $process->isSuccessful() ? $this->normalize($process->getOutput()) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * `wmic` çıxışı UTF-16LE (BOM ilə) gəlir — çevrilməsə regex heç nə tapmır.
     * Digər hallarda mətn olduğu kimi qaytarılır.
     */
    private function normalize(string $output): string
    {
        if (!str_starts_with($output, "\xFF\xFE")) {
            return $output;
        }

        $converted = @mb_convert_encoding(substr($output, 2), 'UTF-8', 'UTF-16LE');

        return is_string($converted) ? $converted : $output;
    }
}
