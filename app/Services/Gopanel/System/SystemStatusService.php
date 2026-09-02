<?php

declare(strict_types=1);

namespace App\Services\Gopanel\System;

use App\Enums\Gopanel\BackupType;
use App\Helpers\Gopanel\ServerMetricsHelper;
use App\Queries\Gopanel\Backup\BackupQuery;
use App\Queries\Gopanel\System\QueueQuery;
use App\Support\Files\ByteSize;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * «Sistem vəziyyəti» səhifəsinin bütün məlumatını hazırlayır.
 *
 * Səhifə bir neçə saniyədən bir yenilənir, ona görə burada AĞIR əməliyyat
 * yoxdur: `public/site` kimi on minlərlə fayllı qovluq gəzilmir, yalnız
 * sayğac sorğuları və `/proc` oxunuşları edilir.
 *
 * Blade-ə hazır (formatlanmış) dəyər verilir — şablonda hesablama olmur
 * (bax: 01-umumi.md § 3).
 */
class SystemStatusService
{
    public function __construct(
        private readonly ServerMetricsHelper $metrics,
        private readonly QueueQuery $queue,
        private readonly BackupQuery $backups,
    ) {
    }

    /**
     * Səhifənin canlı hissəsi — hər yenilənmədə oxunur.
     */
    public function snapshot(): array
    {
        return [
            'cpu'        => $this->cpu(),
            'memory'     => $this->memory(),
            'disk'       => $this->disk(),
            'php'        => $this->php(),
            'queue'      => $this->queueCard(),
            'pending'    => $this->pendingJobs(),
            'failed'     => $this->failedJobs(),
            'scheduler'  => $this->scheduler(),
            'storage'    => $this->storage(),
            'backup'     => $this->backup(),
            'database'   => $this->database(),
            'server'     => $this->server(),
            'checked_at' => Carbon::now()->format('d.m.Y H:i:s'),
        ];
    }

    /**
     * Yalnız qrafiklərə lazım olan rəqəmlər — JSON cavabının yüngül hissəsi.
     */
    public function gauges(array $snapshot): array
    {
        // Rəng «ton» adı ilə gedir (success/warning/danger) — həddlər config-də
        // hesablanır, JS-də təkrarlanmır.
        return [
            'cpu'    => ['value' => $snapshot['cpu']['percent'], 'tone' => $snapshot['cpu']['tone']],
            'memory' => ['value' => $snapshot['memory']['percent'], 'tone' => $snapshot['memory']['tone']],
            'disk'   => ['value' => $snapshot['disk']['percent'], 'tone' => $snapshot['disk']['tone']],
        ];
    }

    /** Səhifə ilk açılanda bir dəfə oxunur — yenilənmə sorğularında yox. */
    public function crontab(): array
    {
        if (!config('gopanel.system_status.show_crontab')) {
            return ['available' => false, 'lines' => [], 'note' => 'Konfiqurasiyada söndürülüb.'];
        }

        $output = $this->run(['crontab', '-l']);

        if ($output === null) {
            return [
                'available' => false,
                'lines'     => [],
                'note'      => 'Veb server istifadəçisinin crontab-ı oxunmadı. '
                    . 'Cron adətən başqa istifadəçidə (root və ya deploy istifadəçisi) qurulur — '
                    . 'onun işlədiyini aşağıdakı «Planlaşdırıcı» göstəricisi təsdiqləyir.',
            ];
        }

        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R/', $output) ?: []),
            static fn (string $line) => $line !== ''
        ));

        return ['available' => true, 'lines' => $lines, 'note' => null];
    }

    // ── Göstəricilər ────────────────────────────────────────────────────

    private function cpu(): array
    {
        $cpu     = $this->metrics->cpu();
        $percent = $cpu['usage'] ?? $cpu['load_percent'];

        return [
            'supported'    => $cpu['supported'],
            'percent'      => $percent,
            'percent_text' => $percent === null ? '—' : $percent . '%',
            'tone'         => $this->tone($percent),
            'note'         => $cpu['note'],
            'rows'         => [
                ['label' => 'Nüvə sayı', 'value' => $cpu['cores'] ? $cpu['cores'] . ' ədəd' : '—'],
                ['label' => 'Yük (1/5/15 dəq.)', 'value' => $cpu['load']
                    ? $cpu['load']['1'] . ' / ' . $cpu['load']['5'] . ' / ' . $cpu['load']['15']
                    : '—'],
                ['label' => 'Prosessor', 'value' => $cpu['model'] ?: '—'],
            ],
        ];
    }

    private function memory(): array
    {
        $memory = $this->metrics->memory();

        return [
            'supported'    => $memory['supported'],
            'percent'      => $memory['percent'],
            'percent_text' => $memory['percent'] === null ? '—' : $memory['percent'] . '%',
            'tone'         => $this->tone($memory['percent']),
            'note'         => $memory['supported'] ? null : 'Bu göstərici yalnız serverdə (Linux) oxunur.',
            'rows'         => [
                ['label' => 'İstifadə olunan', 'value' => $this->bytes($memory['used'])],
                ['label' => 'Boş', 'value' => $this->bytes($memory['free'])],
                ['label' => 'Ümumi', 'value' => $this->bytes($memory['total'])],
                ['label' => 'Swap', 'value' => $memory['swap_total']
                    ? $this->bytes($memory['swap_used']) . ' / ' . $this->bytes($memory['swap_total'])
                    : '—'],
            ],
        ];
    }

    private function disk(): array
    {
        $disk = $this->metrics->disk();

        return [
            'supported'    => $disk['supported'],
            'percent'      => $disk['percent'],
            'percent_text' => $disk['percent'] === null ? '—' : $disk['percent'] . '%',
            'tone'         => $this->tone($disk['percent']),
            'note'         => null,
            'rows'         => [
                ['label' => 'İstifadə olunan', 'value' => $this->bytes($disk['used'])],
                ['label' => 'Boş', 'value' => $this->bytes($disk['free'])],
                ['label' => 'Ümumi', 'value' => $this->bytes($disk['total'])],
                ['label' => 'Bölmə', 'value' => $disk['path']],
            ],
        ];
    }

    /** PHP prosesinin öz göstəriciləri — «etiket → dəyər» kartı. */
    private function php(): array
    {
        return [
            'title' => 'PHP',
            'icon'  => 'fab fa-php',
            'rows'  => [
                ['label' => 'Versiya', 'value' => PHP_VERSION],
                ['label' => 'Yaddaş limiti', 'value' => (string) ini_get('memory_limit')],
                ['label' => 'İşlədilən yaddaş', 'value' => $this->bytes(memory_get_usage(true))],
                ['label' => 'Pik yaddaş', 'value' => $this->bytes(memory_get_peak_usage(true))],
                ['label' => 'İcra limiti', 'value' => ini_get('max_execution_time') . ' san.'],
                ['label' => 'Fayl yükləmə limiti', 'value' => (string) ini_get('upload_max_filesize')],
                ['label' => 'POST limiti', 'value' => (string) ini_get('post_max_size')],
                ['label' => 'OPcache', 'value' => $this->opcacheEnabled() ? 'aktiv' : 'söndürülüb'],
            ],
        ];
    }

    // ── Növbə (queue) ───────────────────────────────────────────────────

    private function queueCard(): array
    {
        $driver = (string) config('queue.default');
        $base   = [
            'driver'      => $driver,
            'supported'   => false,
            'pending'     => 0,
            'running'     => 0,
            'failed'      => 0,
            'oldest_text' => '—',
            'warning'     => null,
            'queues'      => [],
        ];

        // `sync` sürücüsündə iş növbəyə düşmür, sorğunun içində icra olunur.
        if ($driver === 'sync') {
            $base['warning'] = 'Növbə sürücüsü `sync`-dir: işlər növbəyə düşmür, '
                . 'sorğunun içində dərhal icra olunur. Serverdə `QUEUE_CONNECTION=database` olmalıdır.';

            return $base;
        }

        if (!$this->queue->hasJobsTable()) {
            $base['warning'] = '`jobs` cədvəli yoxdur — `php artisan queue:table` və `migrate` işlədilməlidir.';

            return $base;
        }

        $base['supported'] = true;
        $base['pending']   = $this->queue->pendingCount();
        $base['running']   = $this->queue->runningCount();
        $base['failed']    = $this->queue->failedCount();
        $base['queues']    = $this->queue->byQueue();

        $waiting = $this->queue->oldestWaitingSeconds();

        if ($waiting !== null) {
            $base['oldest_text'] = $this->metrics->duration($waiting) ?? '—';

            if ($waiting > (int) config('gopanel.system_status.stale_job_seconds')) {
                $base['warning'] = 'Növbədəki ən köhnə iş ' . $base['oldest_text']
                    . ' gözləyir — queue worker dayanmış ola bilər.';
            }
        }

        return $base;
    }

    /** Növbədə gözləyən işlər. */
    private function pendingJobs(): array
    {
        if (config('queue.default') === 'sync') {
            return [];
        }

        $rows = $this->queue->latestJobs((int) config('gopanel.system_status.job_list_limit'));

        return array_map(fn ($row) => [
            'id'         => (int) $row->id,
            'name'       => $this->jobName($row->payload),
            'queue'      => (string) $row->queue,
            'attempts'   => (int) $row->attempts,
            'state'      => $row->reserved_at ? 'İşləyir' : 'Gözləyir',
            'tone'       => $row->reserved_at ? 'info' : 'warning',
            'waiting'    => $this->metrics->duration(max(time() - (int) $row->created_at, 0)) ?? '—',
            'created_at' => Carbon::createFromTimestamp((int) $row->created_at)->format('d.m.Y H:i'),
        ], $rows);
    }

    /** Uğursuz işlər. */
    private function failedJobs(): array
    {
        $rows = $this->queue->latestFailedJobs((int) config('gopanel.system_status.job_list_limit'));

        return array_map(fn ($row) => [
            'id'        => (int) $row->id,
            'name'      => $this->jobName($row->payload),
            'queue'     => (string) $row->queue,
            'reason'    => $this->firstLine((string) $row->exception),
            'failed_at' => Carbon::parse($row->failed_at)->format('d.m.Y H:i'),
        ], $rows);
    }

    // ── Planlaşdırıcı (cron) ────────────────────────────────────────────

    /**
     * Laravel planlaşdırıcısı: cron onu həqiqətən çağırırmı və hansı işlər var.
     *
     * «Çağırılırmı» sualına heartbeat faylı cavab verir — onu `Kernel::schedule()`
     * hər dəqiqə yeniləyir. Fayl köhnədirsə deməli `schedule:run` işləmir.
     */
    private function scheduler(): array
    {
        $file      = (string) config('gopanel.system_status.heartbeat_file');
        $timestamp = is_file($file) ? (int) trim((string) @file_get_contents($file)) : 0;
        $age       = $timestamp > 0 ? max(time() - $timestamp, 0) : null;
        $stale     = (int) config('gopanel.system_status.scheduler_stale_seconds');

        $alive = $age !== null && $age <= $stale;

        return [
            'alive'      => $alive,
            'tone'       => $alive ? 'success' : 'danger',
            'state_text' => $alive ? 'İşləyir' : ($timestamp > 0 ? 'Dayanıb' : 'Heç vaxt işləməyib'),
            'last_text'  => $timestamp > 0
                ? Carbon::createFromTimestamp($timestamp)->format('d.m.Y H:i:s')
                : '—',
            'ago_text'   => $age === null ? '—' : ($this->metrics->duration($age) . ' əvvəl'),
            'hint'       => $alive
                ? null
                : 'Serverin crontab-ında bu sətir olmalıdır: '
                    . '* * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1',
            'events'     => $this->scheduledEvents(),
        ];
    }

    /**
     * `php artisan schedule:list`-in panel versiyası.
     *
     * Konsol kernel-i HTTP sorğusunda özü qurulmur — onu container-dən
     * çıxarmaq `Kernel::schedule()`-i işə salır və işlər siyahıya düşür.
     */
    private function scheduledEvents(): array
    {
        try {
            app(ConsoleKernel::class);
            $events = app(Schedule::class)->events();
        } catch (Throwable) {
            return [];
        }

        return array_map(function (Event $event) {
            $next = null;

            try {
                $next = $event->nextRunDate()->format('d.m.Y H:i');
            } catch (Throwable) {
                // Yanlış cron ifadəsi bütün səhifəni sındırmamalıdır
            }

            return [
                'name'       => $this->eventName($event),
                'expression' => $event->expression,
                'next'       => $next ?: '—',
                'timezone'   => (string) ($event->timezone ?: config('app.timezone')),
            ];
        }, array_values($events));
    }

    private function eventName(Event $event): string
    {
        if (!empty($event->description)) {
            return (string) $event->description;
        }

        if (!empty($event->command)) {
            // Komanda tam yolla gəlir: '.../php' 'artisan' logs:cleanup
            $command = preg_replace('/^.*artisan.\s*/', 'artisan ', (string) $event->command);

            return trim(str_replace(["'", '"'], '', (string) $command));
        }

        return 'Closure';
    }

    // ── Disk / arxiv / baza ─────────────────────────────────────────────

    /** Storage qovluğunun tutduğu yer — kiçik qovluqlar, gəzmə ucuzdur. */
    private function storage(): array
    {
        $logs       = storage_path('logs');
        $backupRoot = storage_path('app/' . trim((string) config('gopanel.backup.root', 'backups'), '/'));

        return [
            'title' => 'Storage qovluğu',
            'icon'  => 'fas fa-folder-open',
            // Diqqət: burada YALNIZ az fayllı qovluqlar ölçülür. `framework/cache`
            // və `framework/sessions` on minlərlə fayl saxlaya bilər — səhifə
            // bir neçə saniyədən bir yeniləndiyi üçün onlar rekursiv gəzilmir,
            // sessiyalar isə limitli sayılır.
            'rows'  => [
                ['label' => 'Backup arxivləri', 'value' => $this->bytes($this->directorySize($backupRoot))],
                ['label' => 'Log faylları', 'value' => $this->bytes($this->directorySize($logs))],
                ['label' => 'Log fayllarının sayı', 'value' => (string) count(glob($logs . '/*.log') ?: [])],
                ['label' => 'Aktiv sessiyalar', 'value' => $this->countFiles(storage_path('framework/sessions'))],
            ],
        ];
    }

    private function backup(): array
    {
        $card = ['title' => 'Ehtiyat nüsxələr', 'icon' => 'fas fa-shield-alt', 'rows' => []];

        if (!Schema::hasTable('backups')) {
            $card['rows'][] = ['label' => 'Vəziyyət', 'value' => 'Backup cədvəli hələ yaradılmayıb'];

            return $card;
        }

        $last = fn (BackupType $type): string => $this->backups->lastCompleted($type)
            ?->finished_at?->format('d.m.Y H:i') ?? 'hələ çıxarılmayıb';

        $card['rows'] = [
            ['label' => 'Sonuncu baza arxivi', 'value' => $last(BackupType::Database)],
            ['label' => 'Sonuncu fayl arxivi', 'value' => $last(BackupType::Files)],
            ['label' => 'Arxivlərin ümumi ölçüsü', 'value' => ByteSize::human($this->backups->completedSize())],
            ['label' => 'Uğursuz arxivlər', 'value' => (string) $this->backups->failedCount()],
        ];

        return $card;
    }

    private function database(): array
    {
        $stats = $this->queue->databaseStats();

        return [
            'title' => 'Baza',
            'icon'  => 'fas fa-database',
            'rows'  => [
                ['label' => 'Bağlantı', 'value' => (string) config('database.default')],
                ['label' => 'Baza adı', 'value' => $stats['name'] ?? '—'],
                ['label' => 'Server versiyası', 'value' => $stats['version'] ?? '—'],
                ['label' => 'Ölçü', 'value' => $this->bytes($stats['size'])],
                ['label' => 'Cədvəl sayı', 'value' => $stats['tables'] === null ? '—' : (string) $stats['tables']],
            ],
        ];
    }

    private function server(): array
    {
        return [
            'title' => 'Server',
            'icon'  => 'fas fa-server',
            'rows'  => [
                ['label' => 'Server adı', 'value' => (string) (gethostname() ?: '—')],
                ['label' => 'Əməliyyat sistemi', 'value' => trim(php_uname('s') . ' ' . php_uname('r'))],
                ['label' => 'Veb server', 'value' => (string) (request()->server('SERVER_SOFTWARE') ?: '—')],
                ['label' => 'İşləmə müddəti', 'value' => $this->metrics->duration($this->metrics->uptimeSeconds()) ?? '—'],
                ['label' => 'Laravel', 'value' => app()->version()],
                ['label' => 'Mühit', 'value' => (string) config('app.env')],
                [
                    'label' => 'Debug rejimi',
                    'value' => config('app.debug') ? 'açıq' : 'bağlı',
                    // Prod-da debug açıq qalmaq təhlükəlidir — göz deysin deyə vurğulanır
                    'tone'  => config('app.debug') ? 'danger' : 'success',
                ],
                ['label' => 'Keş sürücüsü', 'value' => (string) config('cache.default')],
                ['label' => 'Sessiya sürücüsü', 'value' => (string) config('session.driver')],
                ['label' => 'Saat qurşağı', 'value' => (string) config('app.timezone')],
                ['label' => 'Server vaxtı', 'value' => Carbon::now()->format('d.m.Y H:i:s')],
            ],
        ];
    }

    // ── Köməkçilər ──────────────────────────────────────────────────────

    /** Faizə görə rəng tonu — hədlər config-dədir. */
    private function tone(?float $percent): string
    {
        if ($percent === null) {
            return 'secondary';
        }

        $thresholds = (array) config('gopanel.system_status.thresholds');

        if ($percent >= (float) ($thresholds['danger'] ?? 90)) {
            return 'danger';
        }

        if ($percent >= (float) ($thresholds['warning'] ?? 75)) {
            return 'warning';
        }

        return 'success';
    }

    private function bytes(?int $bytes): string
    {
        return $bytes === null ? '—' : ByteSize::human($bytes);
    }

    /** Job payload-ından oxunaqlı sinif adı. */
    private function jobName(?string $payload): string
    {
        $data = json_decode((string) $payload, true);

        if (!is_array($data)) {
            return '—';
        }

        $name = (string) ($data['displayName'] ?? $data['job'] ?? '—');

        return class_basename($name);
    }

    /** Exception mətninin yalnız birinci sətri — cədvəl dağılmasın. */
    private function firstLine(string $exception): string
    {
        $line = trim((string) strtok($exception, "\n"));

        return mb_strlen($line) > 160 ? mb_substr($line, 0, 160) . '…' : $line;
    }

    /**
     * Qovluqdakı elementləri sayır, amma limitə çatanda dayanır.
     *
     * Sessiya qovluğunda on minlərlə fayl ola bilər — səhifə tez-tez
     * yeniləndiyi üçün tam sayım bahalıdır, dəqiq rəqəm isə lazım deyil.
     */
    private function countFiles(string $path, int $limit = 5000): string
    {
        if (!is_dir($path)) {
            return '0';
        }

        $count = 0;

        try {
            foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $ignored) {
                if (++$count >= $limit) {
                    return $limit . '+';
                }
            }
        } catch (Throwable) {
            return (string) $count;
        }

        return (string) $count;
    }

    /** Qovluğun ölçüsü — yalnız kiçik qovluqlar üçün işlədilir. */
    private function directorySize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $total = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $total += $file->getSize();
                }
            }
        } catch (Throwable) {
            return $total;
        }

        return $total;
    }

    private function opcacheEnabled(): bool
    {
        if (!function_exists('opcache_get_status')) {
            return false;
        }

        try {
            $status = @opcache_get_status(false);
        } catch (Throwable) {
            return false;
        }

        return is_array($status) && ($status['opcache_enabled'] ?? false);
    }

    /**
     * Kənar proses — alınmazsa `null`.
     *
     * @param  list<string>  $command
     */
    private function run(array $command): ?string
    {
        try {
            $process = new Process($command);
            $process->setTimeout(5.0);
            $process->run();

            return $process->isSuccessful() ? $process->getOutput() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
