<?php

namespace App\Services\Activity;

use App\Contracts\CustomLogInterface;
use App\Models\Activity\FileLog;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Psr\Log\LoggerInterface;

class LogService
{

    protected string|null $channel;
    protected bool $saveDatabaseEnable = true;

    public LoggerInterface $logging;


    public function __construct($channel = null, $saveDatabaseEnable = true)
    {
        $this->channel              = $channel;
        $this->saveDatabaseEnable   = $saveDatabaseEnable;
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


    private function logMessage(string $level, string $message, $context = [])
    {
        if ($context instanceof Model) {
            $context = $context->toArray();
        }

        if ($context instanceof JsonResource) {
            $context = $context->toArray(request());
        }

        if (env("APP_ENV") != 'local')
            $context    = $this->sanitizeContext($context);

        $this->logging->$level($message, [
            'data'      => $context,
            'lg-detail' => getLogDetails()
        ]);
        if ($this->saveDatabaseEnable)
            $this->logToDatabase($level, $message, $context);
        return $this;
    }

    public function __call($method, $arguments)
    {
        $logLevels = config("custom.logging.levels");
        if (in_array($method, $logLevels)) {
            $message = $arguments[0] ?? '';
            $context = $arguments[1] ?? [];
            return $this->logMessage($method, $message, $context);
        }
        throw new BadMethodCallException("Method [$method] does not exist.");
    }

    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = config("custom.logging.sensitiveKeys");
        foreach ($context as $key => &$value) {
            if (is_array($value)) {
                $value = $this->sanitizeContext($value); // Recursive cleaning
            } elseif (in_array(strtolower($key), $sensitiveKeys)) {
                // Masking with stars based on the length of the value
                // $value = str_repeat('*', strlen($value));
                $value = '[' . $key . ']';
            }
        }

        return $context;
    }

    protected function to_array($context)
    {
        return array_map(function ($item) {
            if ($item instanceof Model) {
                return $item->toArray();
            }
            return $item;
        }, $context);
    }

    // save to database
    private function logToDatabase(string $level, string $message, array $context)
    {
        if (!Schema::hasTable('file_logs'))
            return false;
        $fileog = new FileLog();
        $fileog->admin_id           = Auth::guard('gopanel')->check() ? Auth::guard('gopanel')->user()->id : NULL;
        $fileog->user_id            = Auth::guard('web')->check() ? Auth::guard('web')->user()->id : NULL;
        $fileog->channel            = $this->channel;
        $fileog->level              = $level;
        $fileog->message            = $message;
        $fileog->context            = $context;
        $fileog->log_details        = getLogDetails();
        $fileog->save();
    }
}
