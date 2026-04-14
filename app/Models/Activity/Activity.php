<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    /**
     * Get the model that caused the activity.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the model associated with the activity.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Accessor-lar ────────────────────────────────────────────────

    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            default   => 'secondary',
        };
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'created' => 'Yaradıldı',
            'updated' => 'Yeniləndi',
            'deleted' => 'Silindi',
            default   => $this->event ?? '-',
        };
    }

    public function getEventBadgeAttribute(): string
    {
        return '<span class="badge bg-' . $this->event_color . '" style="font-size:13px;padding:5px 10px;">' . $this->event_label . '</span>';
    }

    public function getLogNameTitleAttribute(): string
    {
        $allMessages = config('custom.activity_messages', []);
        return $allMessages[$this->log_name]['title'] ?? $this->log_name ?: '-';
    }

    public function getLogNameBadgeAttribute(): string
    {
        return '<span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:13px;padding:5px 10px;">' . e($this->log_name_title) . '</span>';
    }

    public function getCauserNameAttribute(): string
    {
        if (!$this->causer) {
            return '-';
        }

        $causer = $this->causer;
        $name = $causer->full_name ?? (($causer->name ?? '') . ' ' . ($causer->surname ?? ''));
        return trim($name) ?: '-';
    }

    public function getDescriptionShortAttribute(): string
    {
        $msg = $this->description ?? '-';
        $short = mb_substr($msg, 0, 60);
        $dots = mb_strlen($msg) > 60 ? '...' : '';
        return '<span title="' . e($msg) . '">' . e($short) . $dots . '</span>';
    }

    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : '-';
    }
}
