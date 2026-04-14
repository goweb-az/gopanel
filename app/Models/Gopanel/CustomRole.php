<?php

namespace App\Models\Gopanel;

use App\Helpers\Common\ActivityLogHelper;
use App\Traits\HasRouteKey;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role;

class CustomRole extends Role
{
    use HasRouteKey, LogsActivity;

    protected $fillable = [
        'name',
        'guard_name',
    ];

    public function getActivitylogOptions(): LogOptions
    {
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

    public function getPermissionsCountAttribute(): string|null
    {
        return ' İcazələri [' . $this->permissions()->count() . ']';
    }
}
