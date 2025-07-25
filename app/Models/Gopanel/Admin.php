<?php

namespace App\Models\Gopanel;

use App\Http\Middleware\Gopanel;
use App\Traits\AddUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use AddUuid, Notifiable, HasFactory, SoftDeletes, HasRoles;

    protected $logEnabled = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'is_active',
        'is_super',
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


    public function getIsActiveBtnAttribute()
    {
        return app('gopanel')->is_active_btn($this, "is_active", $this->is_active == 1);
    }

    public function getIsSuperBtnAttribute()
    {
        return app('gopanel')->is_active_btn($this, "is_super", $this->is_super == 1, [], null, "Bəli", "Xeyr");
    }
}
