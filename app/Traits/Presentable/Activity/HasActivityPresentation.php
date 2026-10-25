<?php

namespace App\Traits\Presentable\Activity;

/**
 * `Activity` (fəaliyyət jurnalı) modelinin göstəriş accessor-ları.
 *
 * Niyə ayrıca trait: model Spatie-nin `Activity`-sindən törəyir və orada yalnız
 * əlaqələr qalmalıdır; siyahı/detal modalının etiket və nişanları bura yığılır
 * (docs/rules/presentable-traits.md). Adlar köçürülərkən DƏYİŞMƏYİB -
 * `ActivityLogDatatable` və `activity_logs/inc/show.blade.php` onlardan asılıdır.
 */
trait HasActivityPresentation
{
    /** Bootstrap tonu (köhnə ad - `_color` olsa da hex deyil, ton qaytarır). */
    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            default   => 'secondary',
        };
    }

    /** Lüğətə uyğun sinonim: `event_label` + `event_class` cütü. */
    public function getEventClassAttribute(): string
    {
        return $this->event_color;
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

    /** Datatable hüceyrəsi. Xam `event` dəyəri etiketə düşə bildiyi üçün qaçırılır. */
    public function getEventBadgeAttribute(): string
    {
        return '<span class="badge bg-' . e($this->event_color) . '" style="font-size:13px;padding:5px 10px;">' . e($this->event_label) . '</span>';
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

    /** Jurnal sabit, dildən asılı olmayan formatda göstərilir (axtarış/kopyalama üçün). */
    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : '-';
    }
}
