<?php

namespace App\Traits\Activity;

use App\Helpers\Common\ActivityLogHelper;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsAdminActivity
{
    use LogsActivity {
        shouldLogEvent as traitShouldLogEvent;
    }

    protected function shouldLogEvent(string $eventName): bool
    {
        if (property_exists($this, 'logEnabled') && $this->logEnabled === false) {
            return false;
        }

        $modelName = class_basename($this);

        if (!config("custom.activity_messages.{$modelName}")) {
            return false;
        }

        return $this->traitShouldLogEvent($eventName);
    }

    public function getActivitylogOptions(): LogOptions
    {
        if (property_exists($this, 'logEnabled') && $this->logEnabled === false) {
            return LogOptions::defaults()
                ->dontSubmitEmptyLogs()
                ->logOnly([]);
        }

        return LogOptions::defaults()
            ->logOnly($this->getFillable())
            ->useLogName(class_basename($this))
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        if (Auth::guard('gopanel')->check()) {
            $activity->causer()->associate(Auth::guard('gopanel')->user());
        } elseif (Auth::guard('web')->check()) {
            $activity->causer()->associate(Auth::guard('web')->user());
        }

        ActivityLogHelper::resolveDescription($this, $activity, $eventName);
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'causer');
    }
}
