<?php

namespace App\Models\Activity;

use App\Models\BaseModel;
use App\Models\Gopanel\Admin;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FileLog extends BaseModel
{
    use HasFactory;

    protected $logEnabled = false;

    protected $fillable = [
        'channel',
        'level',
        'message',
        'context',
        'log_details'
    ];

    protected $casts = [
        'context' => 'array',
        'log_details' => 'array',
    ];


    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUserNameAttribute()
    {
        return $this?->user?->full_info ?? null;
    }

    public function getAdminNameAttribute()
    {
        return $this?->admin?->full_name ?? NULL;
    }


    // public function getUserLinkAttribute()
    // {
    //     return !empty($this->user_name) ? '<a href="' . route("gopanel.file-logs.index", ['user_id' => $this->user->id]) . '">' . $this->user_name . '</a>' : null;
    // }

    // public function getAdminLinkAttribute()
    // {
    //     return !empty($this->admin_name) ? '<a href="' . route("gopanel.file-logs.index", ['admin_id' => $this->admin->id]) . '">' . $this->admin_name . '</a>' : null;
    // }

    // public function getChannelLinkAttribute()
    // {
    //     return !empty($this->channel) ? '<a href="' . route("gopanel.file-logs.index", ['channel' => $this->channel]) . '">' . $this->channel . '</a>' : null;
    // }

    // public function getLevelLinkAttribute()
    // {
    //     return !empty($this->level) ? '<a href="' . route("gopanel.file-logs.index", ['level' => $this->level]) . '">' . $this->level . '</a>' : null;
    // }
}
