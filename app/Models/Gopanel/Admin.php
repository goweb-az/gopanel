<?php

namespace App\Models\Gopanel;

use App\Helpers\Common\ActivityLogHelper;
use App\Traits\AddUuid;
use App\Traits\HasRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use AddUuid, HasRouteKey, Notifiable, HasFactory, SoftDeletes, HasRoles, LogsActivity;

    protected $fillable = [
        'uid',
        'full_name',
        'email',
        'password',
        'is_active',
        'is_super',
        'image',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

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

    public function getIsActiveBtnAttribute()
    {
        return app('gopanel')->toggle_btn($this, 'is_active', $this->is_active == 1);
    }

    public function getIsSuperBtnAttribute()
    {
        return app('gopanel')->toggle_btn($this, 'is_super', $this->is_super == 1, [], null, 'Beli', 'Xeyr');
    }

    public function getRoleSummaryAttribute(): string
    {
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();
        $roleNames = $roles->pluck('name')->filter()->values();

        if ($roleNames->isEmpty()) {
            return '<span class="text-muted">Vəzifə verilməyib</span>';
        }

        $primaryRole = e($roleNames->first());
        $extraCount = max($roleNames->count() - 1, 0);

        if ($extraCount === 0) {
            return "<span class=\"fw-semibold\">{$primaryRole}</span>";
        }

        return "<span class=\"fw-semibold\" title=\"" . e($roleNames->join(', ')) . "\">{$primaryRole}</span> <span class=\"text-muted\">(+{$extraCount})</span>";
    }

    public function getPermissionSummaryAttribute(): string
    {
        $grantedCount = $this->granted_permissions_count;
        $totalCount = $this->getTotalPermissionsCount();

        return "<span class=\"fw-semibold\">{$totalCount}</span><span class=\"text-muted\"> / {$grantedCount}</span>";
    }

    public function getGrantedPermissionsCountAttribute(): int
    {
        return $this->getAllPermissions()->count();
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

    public function getAvatarUrlAttribute(): string
    {
        if (!empty($this->image) && Storage::disk('public')->exists($this->image)) {
            return Storage::disk('public')->url($this->image);
        }

        return Cache::rememberForever("admin_avatar_{$this->id}", function () {
            $name = urlencode($this->full_name ?? 'Admin');
            return "https://ui-avatars.com/api/?name={$name}&background=556ee6&color=fff&size=128&font-size=0.4&rounded=true";
        });
    }
}
