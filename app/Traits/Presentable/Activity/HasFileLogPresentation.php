<?php

namespace App\Traits\Presentable\Activity;

/**
 * `FileLog` (fayl jurnalı) modelinin göstəriş accessor-ları.
 *
 * Niyə: səviyyə → ton/ikon xəritəsi siyahıda, detal modalında və filtrdə eyni
 * olmalıdır; modeldə qalanda hər yeni sütunla birlikdə model şişirdi.
 * Adlar DƏYİŞMƏYİB - `FilelogsDatatable` və `file_logs/inc/show.blade.php` işlədir.
 */
trait HasFileLogPresentation
{
    /**
     * Əvvəlcədən yüklənmiş/tənbəl əlaqədən oxunur. Starter-in `User` modelində
     * `full_info` yoxdur - törəmə layihə əlavə edəndə burada avtomatik görünür.
     */
    public function getUserNameAttribute()
    {
        return $this?->user?->full_info ?? null;
    }

    public function getAdminNameAttribute()
    {
        return $this?->admin?->full_name ?? NULL;
    }

    /** Bootstrap tonu (köhnə ad). */
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

    /** Lüğətə uyğun sinonim: `level_label` + `level_class` cütü. */
    public function getLevelClassAttribute(): string
    {
        return $this->level_color;
    }

    public function getLevelLabelAttribute(): string
    {
        return strtoupper($this->level ?? '-');
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

    /** Datatable hüceyrəsi; `level` sütunu sərbəst mətndir, ona görə qaçırılır. */
    public function getLevelBadgeAttribute(): string
    {
        return '<span class="badge bg-' . e($this->level_color) . '">' . e($this->level_label) . '</span>';
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
