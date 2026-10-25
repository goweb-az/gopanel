<?php

namespace App\Traits\System;

use Illuminate\Support\Facades\DB;

/**
 * Yüngül icazə yoxlaması - Spatie-nin qlobal registry-sini YÜKLƏMƏDƏN.
 *
 * Problem: Spatie `hasPermissionTo()` ilk çağırışda BÜTÜN sistemin
 * icazə+rol bağlantılarını Eloquent modelləri kimi yaddaşa hidrasiya edir.
 * Rol sayı az olanda hiss olunmur; çox rollu/çox icarəçili layihədə
 * (qrgate: hər şirkətə 3 rol × ~300 icazə) hər sorğu 250+ MB yeyirdi və
 * rol sayı ilə xətti böyüyürdü.
 *
 * Həll: cari modelin icazə ADLARI iki kiçik JOIN sorğusu ilə (rol üzərindən +
 * birbaşa) yüklənir və sorğu boyu memoize olunur.
 *
 * OPT-IN: starter-də heç bir modelə qoşulmayıb - `Admin` Spatie-nin adi
 * yolundan gedir. Qoşmaq üçün modeldə trait əlavə edib `hasPermissionTo()`-nu
 * override et:
 *
 *     public function hasPermissionTo($permission, $guardName = null): bool
 *     {
 *         return is_string($permission)
 *             ? $this->hasLeanPermission($permission, $guardName)
 *             : parent::hasPermissionTo($permission, $guardName);
 *     }
 *
 * Bilərəkdən DƏSTƏKLƏNMƏYƏNLƏR: Spatie wildcard icazələri (`posts.*`) və
 * `teams` rejimi - bunlar lazımdırsa trait işlədilmir.
 * Rol dəyişikliyi növbəti sorğuda görünür (eyni sorğuda lazımdırsa
 * `forgetLeanPermissionCache()`).
 *
 * Mənbə: app.qrgate.loc `LeanPermissionChecks` - cədvəl adları Spatie
 * config-indən (`permission.table_names`) oxunur, hardcode deyil.
 */
trait LeanPermissionChecks
{
    /** @var array<string, array<string, true>> guard → [icazə adı => true] */
    private array $leanPermissionCache = [];

    public function hasLeanPermission(string $permission, ?string $guardName = null): bool
    {
        $guard = $guardName ?: $this->getDefaultGuardName();

        return isset($this->leanPermissionNames($guard)[$permission]);
    }

    /**
     * Modelin bütün icazə adları (guard üzrə) - sorğu boyu keşlənir.
     *
     * @return array<string, true>
     */
    public function leanPermissionNames(string $guard): array
    {
        if (isset($this->leanPermissionCache[$guard])) {
            return $this->leanPermissionCache[$guard];
        }

        $tables = (array) config('permission.table_names', []);
        $permissions = $tables['permissions'] ?? 'permissions';
        $modelHasRoles = $tables['model_has_roles'] ?? 'model_has_roles';
        $modelHasPermissions = $tables['model_has_permissions'] ?? 'model_has_permissions';
        $roleHasPermissions = $tables['role_has_permissions'] ?? 'role_has_permissions';

        $columns = (array) config('permission.column_names', []);
        $modelKey = $columns['model_morph_key'] ?? 'model_id';

        $modelTypes = array_values(array_unique([static::class, $this->getMorphClass()]));

        $viaRoles = DB::table($modelHasRoles . ' as mhr')
            ->join($roleHasPermissions . ' as rhp', 'rhp.role_id', '=', 'mhr.role_id')
            ->join($permissions . ' as p', 'p.id', '=', 'rhp.permission_id')
            ->whereIn('mhr.model_type', $modelTypes)
            ->where('mhr.' . $modelKey, $this->getKey())
            ->where('p.guard_name', $guard)
            ->pluck('p.name');

        $direct = DB::table($modelHasPermissions . ' as mhp')
            ->join($permissions . ' as p', 'p.id', '=', 'mhp.permission_id')
            ->whereIn('mhp.model_type', $modelTypes)
            ->where('mhp.' . $modelKey, $this->getKey())
            ->where('p.guard_name', $guard)
            ->pluck('p.name');

        return $this->leanPermissionCache[$guard] = $viaRoles->merge($direct)
            ->unique()
            ->mapWithKeys(fn ($name) => [(string) $name => true])
            ->all();
    }

    /** Rol/icazə dəyişikliyindən sonra eyni sorğuda təzə oxumaq üçün. */
    public function forgetLeanPermissionCache(): void
    {
        $this->leanPermissionCache = [];
    }
}
