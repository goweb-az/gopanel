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

    public function getPermissionsCountAttribute(): string
    {
        $permissions = $this->relationLoaded('permissions') ? $this->permissions : $this->permissions()->get();
        $count = $permissions->count();
        $groups = $permissions->pluck('group')->filter()->unique()->count();

        if ($count === 0) {
            return '<span class="text-muted">İcazə verilməyib</span>';
        }

        $groupText = $groups ? " <span class=\"text-muted\">{$groups} qrup</span>" : '';

        return "<span class=\"fw-semibold\">{$count} icazə</span>{$groupText}";
    }

    public function getAssignedAdminsCountAttribute(): string
    {
        $users = $this->relationLoaded('users') ? $this->users : $this->users()->get();
        $count = $users->count();

        if ($count === 0) {
            return '<span class="text-muted">Heç kimə verilməyib</span>';
        }

        if ($count === 1) {
            return '<span class="fw-semibold">1 nəfərə verilib</span>';
        }

        return "<span class=\"fw-semibold\">{$count} nəfərə verilib</span>";
    }
}
