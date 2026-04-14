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
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use AddUuid, HasRouteKey, Notifiable, HasFactory, SoftDeletes, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uid',
        'full_name',
        'email',
        'password',
        'is_active',
        'is_super',
        'image',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
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
        return app('gopanel')->toggle_btn($this, "is_active", $this->is_active == 1);
    }

    public function getIsSuperBtnAttribute()
    {
        return app('gopanel')->toggle_btn($this, "is_super", $this->is_super == 1, [], null, "Bəli", "Xeyr");
    }

    public function getAvatarUrlAttribute(): string
    {
        if (!empty($this->image) && file_exists(public_path($this->image))) {
            return asset($this->image);
        }

        return Cache::rememberForever("admin_avatar_{$this->id}", function () {
            $name = urlencode($this->full_name ?? 'Admin');
            return "https://ui-avatars.com/api/?name={$name}&background=556ee6&color=fff&size=128&font-size=0.4&rounded=true";
        });
    }
}
