<?php

namespace App\Services\Activity;


use App\Models\Activity\FileLog;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

class LogService
{

    protected string|null $channel;
    protected bool $saveDatabaseEnable = true;
    /**
     * Backtrace detalı yazılsınmı. Default `false` - konstruktorla eynidir
     * (bax `5bc202c6`: hər log üçün detal yazmaq həddən artıq səs-küy idi).
     * Xəta səviyyələrində (`error`/`critical`/`alert`/`emergency`) bu bayraqdan
     * ASILI OLMAYARAQ detal yazılır.
     */
    public bool $log_detail = false;

    public LoggerInterface $logging;


    public function __construct($channel = null, $saveDatabaseEnable = true, $log_detail = false)
    {
        $this->channel = $channel;
        $this->saveDatabaseEnable = $saveDatabaseEnable;
        $this->log_detail = $log_detail;
        $this->setChannel($channel);
    }


    private function setChannel($channel)
    {
        // If the channel is null, use Laravel's default log channel
        if ($channel === null) {
            $this->logging = Log::channel(config('logging.default'));
        } else {
            // If the channel is specified, check if it exists in the available log channels
            if (in_array($channel, array_keys(config('logging.channels')))) {
                $this->logging = Log::channel($channel);
            } else {
                // If the channel doesn't exist, use the default channel
                $this->logging = Log::channel(config('logging.default'));
            }
        }
    }

    /**
     * Tez istifadə üçün static method.
     * LogService::channel('cron')->info('mesaj');
     */
    public static function channel(string $channel, $saveDatabaseEnable = true, $log_detail = false): self
    {
        return new self($channel, $saveDatabaseEnable, $log_detail);
    }

    /**
     * Bu nüsxənin BÜTÜN sətirlərinə `log_details` blokunu (request, IP, backtrace) qoşur.
     *
     * Niyə: default `false`-dur - hər `info` sətrinə request dump-u qoşulsa
     * `file_logs` sürətlə şişir. Amma audit izi olan domen hadisələrində
     * (status dəyişikliyi, ödəniş, geri qaytarma) «kim, haradan, hansı sorğu
     * ilə» sualı mütləq lazımdır. Konstruktorun 3-cü mövqeli arqumentinin
     * (`new LogService('x', true, true)`) mənası çağırışda görünmür - fluent
     * forma niyyəti açıq göstərir:
     *
     *   LogService::channel('orders')->withDetails()->info('Status dəyişdi', [...]);
     *
     * Xəta səviyyələrində (`error`...`emergency`) detal bu bayraqdan asılı
     * olmayaraq həmişə yazılır.
     */
    public function withDetails(bool $enabled = true): self
    {
        $this->log_detail = $enabled;

        return $this;
    }

    /**
     * `log_details` bloku bu sətrə qoşulsunmu? Fayl və DB yazısı eyni qaydanı
     * işlətsin deyə tək yerdədir.
     */
    private function shouldAttachDetails(string $level): bool
    {
        return $this->log_detail
            || in_array($level, ['error', 'critical', 'alert', 'emergency'], true);
    }


    /**
     * Xəta loglarına EYNİ formada aktor məlumatı: sayt istifadəçisi (uid, ad)
     * və gopanel admini. Hamısı üzdə görünən identifikatorlardır - axtarışı
     * asanlaşdırır.
     *
     * Heç bir halda istisna atmır: hər hissə ayrıca qorunur - guard/sessiya
     * yoxdursa (console, queue) sahə sadəcə null qalır, log yenə yazılır.
     *
     * Törəmə layihədə əlavə kontekst (şirkət, filial...) lazımdırsa çağıran yer
     * öz açarlarını bu massivə əlavə edir - metodun forması dəyişdirilmir.
     *
     * @return array<string, mixed>
     */
    public static function actorContext(): array
    {
        $ctx = [
            'user_uid' => null,
            'user'     => null,
            'admin_id' => null,
            'admin'    => null,
        ];

        try {
            $user = Auth::guard('web')->user();

            if ($user) {
                $ctx['user_uid'] = $user->uid ?? null;
                $ctx['user']     = $user->full_info ?? ($user->name ?? ($user->email ?? null));
            }
        } catch (Throwable) {
            // guard/sessiya yoxdur (console, queue) - boş qalır
        }

        try {
            $admin = Auth::guard('gopanel')->user();
            if ($admin) {
                $ctx['admin_id'] = $admin->id ?? null;
                $ctx['admin']    = $admin->full_info ?? ($admin->name ?? ($admin->email ?? null));
            }
        } catch (Throwable) {
            // gopanel guard yoxdur - boş qalır
        }

        return $ctx;
    }

    /**
     * `actorContext()`-dən log MESAJINA qoyulan qısa, oxunaqlı etiket.
     * Nümunə: "İstifadəçi: Orxan Əliyev · Admin: admin@site.az"
     * Məlumat yoxdursa boş sətir qaytarır.
     */
    public static function actorLabel(?array $ctx = null): string
    {
        $ctx   = $ctx ?? self::actorContext();
        $parts = [];

        if (!empty($ctx['user'])) {
            $parts[] = 'İstifadəçi: ' . $ctx['user'];
        } elseif (!empty($ctx['user_uid'])) {
            $parts[] = 'İstifadəçi: ' . $ctx['user_uid'];
        }

        if (!empty($ctx['admin'])) {
            $parts[] = 'Admin: ' . $ctx['admin'];
        }

        return implode(' · ', $parts);
    }


    private function logMessage(string $level, string $message, $context = [])
    {
        // Bütün tip-ləri safely array-ə çevir
        $context = $this->normalizeContext($context);

        if (!app()->environment('local')) {
            $context = $this->sanitizeContext($context);
        }

        //Start writing log data
        $logPayload = ['data' => $context];
        if ($this->shouldAttachDetails($level)) {
            $logPayload['log_details'] = $this->safeGetLogDetails();
        }
        $this->logging->$level($message, $logPayload);

        if ($this->saveDatabaseEnable) {
            $this->safeLogToDatabase($level, $message, $context);
        }

        return $this;
    }

    public function __call($method, $arguments)
    {
        // Yanlış metod adı proqramçı səhvidir - try/catch-dən KƏNARDA atılır.
        // Əks halda `$log->inof(...)` kimi yazı səssizcə udulurdu: log yazılmır,
        // amma heç bir siqnal da olmurdu. Aşağıdakı catch yalnız log yazma
        // xətaları üçündür - log sistemi tətbiqi dayandırmamalıdır.
        $logLevels = config("custom.logging.levels");

        if (!in_array($method, $logLevels)) {
            throw new BadMethodCallException("Method [$method] does not exist.");
        }

        try {
            $message = $arguments[0] ?? '';
            $context = $arguments[1] ?? [];

            return $this->logMessage($method, $message, $context);
        } catch (Throwable $th) {
            Log::warning("[LogService] Log yazma xətası: {$th->getMessage()}", [
                'method' => $method,
                'original_message' => $arguments[0] ?? '',
                'exception' => $th->getTraceAsString()
            ]);
        }
    }

    /**
     * Kontekst datasını safely array-ə çevir.
     * Model, JsonResource, Collection, string, null - hamısını tutur.
     */
    private function normalizeContext($context): array
    {
        try {
            if (is_null($context) || $context === '')
                return [];
            if (is_array($context))
                return $context;
            if ($context instanceof Model)
                return $context->toArray();
            if ($context instanceof JsonResource)
                return $context->toArray(request());
            if ($context instanceof Collection)
                return $context->toArray();
            if (is_object($context))
                return (array) $context;
            if (is_string($context))
                return ['raw' => $context];
            return ['raw' => $context];
        } catch (Throwable $e) {
            return [
                '_parse_error' => $e->getMessage(),
                'exception' => $e->getTraceAsString()
            ];
        }
    }

    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = config("custom.logging.sensitiveKeys");
        foreach ($context as $key => &$value) {
            if (is_array($value)) {
                $value = $this->sanitizeContext($value); // Recursive cleaning
            } elseif (in_array(strtolower($key), array_map('strtolower', $sensitiveKeys))) {
                $value = '[' . $key . ']';
            }
        }

        return $context;
    }

    /**
     * Database-ə log yaz - xəta sistemi çökdürməsin
     */
    private function safeLogToDatabase(string $level, string $message, array $context): void
    {
        try {
            $fileLog = new FileLog();
            $fileLog->admin_id = Auth::guard('gopanel')->check() ? Auth::guard('gopanel')->user()->id : null;
            $fileLog->user_id = Auth::guard('web')->check() ? Auth::guard('web')->user()->id : null;
            $fileLog->channel = $this->channel;
            $fileLog->level = $level;
            $fileLog->message = $message;
            $fileLog->context = $context;
            $fileLog->log_details = $this->shouldAttachDetails($level) ? $this->safeGetLogDetails() : null;
            $fileLog->save();
        } catch (Throwable $e) {
            // Log yazma xətası sistemi çökdürməməlidir
            Log::warning("[LogService] DB log yazma xətası: {$e->getMessage()}");
        }
    }

    /**
     * Log details-i safely al
     */
    private function safeGetLogDetails(): ?array
    {
        try {
            return getLogDetails();
        } catch (Throwable $e) {
            return [
                '_error' => 'Log details alına bilmədi: ' . $e->getMessage(),
                'exception' => $e->getTraceAsString()
            ];
        }
    }
}
