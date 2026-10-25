<?php

namespace App\Traits\Presentable\Gopanel;

/**
 * `CustomRole` (vəzifə) modelinin siyahı hüceyrələri.
 *
 * Niyə: model Spatie `Role`-dan törəyir və orada yalnız jurnal ayarları
 * qalmalıdır. Adlar DƏYİŞMƏYİB - vəzifələr datatable-ı işlədir.
 *
 * Qeyd (geriyə uyğunluq istisnası): əlaqə yüklənməyibsə özü yükləyir -
 * siyahıda `with(['permissions', 'users'])` və ya `withCount` verilməlidir.
 */
trait HasCustomRolePresentation
{
    public function getPermissionsCountAttribute(): string
    {
        $permissions = $this->relationLoaded('permissions') ? $this->permissions : $this->permissions()->get();
        $count = $permissions->count();
        $groups = $permissions->pluck('group')->filter()->unique()->count();

        if ($count === 0) {
            return '<span class="text-muted">İcazə verilməyib</span>';
        }

        $groupText = $groups ? " <span class=\"text-muted\">{$groups} qrup</span>" : '';

        return "<span class=\"fw-semibold\">{$count} icazə</span>{$groupText}";
    }

    public function getAssignedAdminsCountAttribute(): string
    {
        $users = $this->relationLoaded('users') ? $this->users : $this->users()->get();
        $count = $users->count();

        if ($count === 0) {
            return '<span class="text-muted">Heç kimə verilməyib</span>';
        }

        if ($count === 1) {
            return '<span class="fw-semibold">1 nəfərə verilib</span>';
        }

        return "<span class=\"fw-semibold\">{$count} nəfərə verilib</span>";
    }
}
