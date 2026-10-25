<?php

namespace App\Models\Gopanel;

use App\Helpers\Common\ActivityLogHelper;
use App\Traits\System\AddUuid;
use App\Traits\System\HasRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\Presentable\Gopanel\HasAdminPresentation;

class Admin extends Authenticatable
{
    use AddUuid, HasRouteKey, Notifiable, HasFactory, SoftDeletes, HasRoles, LogsActivity, HasAdminPresentation;

    protected $fillable = [
        'uid',
        'full_name',
        'email',
        'password',
        'is_active',
        'is_super',
        'image',
    ];

    /*
     * `pin` $fillable-a salınmır: kütləvi doldurma ilə yazıla bilməsin -
     * yalnız `AdminPinService` `forceFill()` ilə yazır.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'pin_updated_at'    => 'datetime',
    ];

    /** Admin həssas əməliyyatlar üçün PIN təyin edibmi (bax AdminPinService). */
    public function hasPin(): bool
    {
        return filled($this->pin);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['full_name', 'email', 'is_active', 'is_super'])
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

    public function getTotalPermissionsCount(): int
    {
        return Cache::rememberForever('gopanel_permissions_total_count', function () {
            $permissionClass = app(PermissionRegistrar::class)->getPermissionClass();

            return $permissionClass::query()
                ->where('guard_name', $this->getDefaultGuardName())
                ->count();
        });
    }
}
