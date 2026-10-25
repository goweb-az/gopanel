<?php

namespace App\Models\Activity;

use App\Models\BaseModel;
use App\Models\Gopanel\Admin;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Presentable\Activity\HasFileLogPresentation;

class FileLog extends BaseModel
{
    use HasFactory, HasFileLogPresentation;

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
}
