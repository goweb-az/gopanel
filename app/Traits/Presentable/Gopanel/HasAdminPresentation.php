<?php

namespace App\Traits\Presentable\Gopanel;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * `Admin` modelinin göstəriş accessor-ları (siyahı sütunları, avatar).
 *
 * Niyə: model autentifikasiya + rol məntiqini daşıyır; cədvəl hüceyrələri bura
 * ayrılıb ki, «bu sütun bazadadır, yoxsa hesablanır?» sualı itməsin.
 * Adlar DƏYİŞMƏYİB - admin datatable-ı, header və profil view-ları işlədir.
 *
 * Qeyd (geriyə uyğunluq istisnası): `role_summary` əlaqə yüklənməyibsə özü
 * yükləyir, `permission_summary` isə Spatie-nin `getAllPermissions()`-ını
 * çağırır. Bu, trait qaydasının (sorğu yoxdur) köhnə istisnasıdır - siyahıda
 * `with('roles')` verilməlidir. Sorğu metodu (`getTotalPermissionsCount`)
 * modeldə qalır.
 */
trait HasAdminPresentation
{
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

    /**
     * Yüklənmiş şəkil yoxdursa adın baş hərflərindən avatar. Xarici URL
     * `rememberForever` ilə keşlənir ki, hər sətirdə `urlencode` + string
     * yığılmasın.
     */
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
