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

    public function getLevelColorAttribute()
    {
        return match ($this->level) {
            'error', 'critical', 'alert', 'emergency' => 'danger',
            'warning' => 'warning',
            'notice'  => 'info',
            'info'    => 'primary',
            'debug'   => 'secondary',
            default   => 'secondary',
        };
    }

    public function getLevelIconAttribute()
    {
        return match ($this->level) {
            'error', 'critical', 'alert', 'emergency' => 'fas fa-exclamation-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'notice', 'info' => 'fas fa-info-circle',
            'debug' => 'fas fa-bug',
            default => 'fas fa-file-alt',
        };
    }

    public function getLevelBadgeAttribute(): string
    {
        return '<span class="badge bg-' . $this->level_color . '">' . strtoupper($this->level ?? '-') . '</span>';
    }

    public function getMessageShortAttribute(): string
    {
        $msg = $this->message ?? '-';
        $short = mb_substr($msg, 0, 60);
        $dots = mb_strlen($msg) > 60 ? '...' : '';
        return '<span title="' . e($msg) . '">' . e($short) . $dots . '</span>';
    }

    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : '-';
    }
}
