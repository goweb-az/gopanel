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
    public bool $log_detail = true;

    public LoggerInterface $logging;


    public function __construct($channel = null, $saveDatabaseEnable = true, $log_detail = true)
    {
        $this->channel              = $channel;
        $this->saveDatabaseEnable   = $saveDatabaseEnable;
        $this->log_detail           = $log_detail;
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


    private function logMessage(string $level, string $message, $context = [])
    {
        // Bütün tip-ləri safely array-ə çevir
        $context = $this->normalizeContext($context);

        if (!app()->environment('local')) {
            $context = $this->sanitizeContext($context);
        }

        //Start writing log data
        $logPayload = ['data' => $context];
        $errorLevels = ['error', 'critical', 'alert', 'emergency'];
        if ($this->log_detail && in_array($level, $errorLevels)) {
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
        try {
            $logLevels = config("custom.logging.levels");
            if (in_array($method, $logLevels)) {
                $message = $arguments[0] ?? '';
                $context = $arguments[1] ?? [];
                return $this->logMessage($method, $message, $context);
            }
            throw new BadMethodCallException("Method [$method] does not exist.");
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
     * Model, JsonResource, Collection, string, null — hamısını tutur.
     */
    private function normalizeContext($context): array
    {
        try {
            if (is_null($context) || $context === '') return [];
            if (is_array($context)) return $context;
            if ($context instanceof Model) return $context->toArray();
            if ($context instanceof JsonResource) return $context->toArray(request());
            if ($context instanceof Collection) return $context->toArray();
            if (is_object($context)) return (array) $context;
            if (is_string($context)) return ['raw' => $context];
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
     * Database-ə log yaz — xəta sistemi çökdürməsin
     */
    private function safeLogToDatabase(string $level, string $message, array $context): void
    {
        try {
            $fileLog = new FileLog();
            $fileLog->admin_id    = Auth::guard('gopanel')->check() ? Auth::guard('gopanel')->user()->id : null;
            $fileLog->user_id     = Auth::guard('web')->check() ? Auth::guard('web')->user()->id : null;
            // $fileLog->company_id  = Auth::guard('web')->check() ? Auth::guard('web')->user()->current_company_id : null;
            $fileLog->channel     = $this->channel;
            $fileLog->level       = $level;
            $fileLog->message     = $message;
            $fileLog->context     = $context;
            $errorLevels = ['error', 'critical', 'alert', 'emergency'];
            $fileLog->log_details = in_array($level, $errorLevels) ? $this->safeGetLogDetails() : null;
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
