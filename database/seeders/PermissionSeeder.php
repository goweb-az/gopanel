<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gopanel\Admin;
use App\Models\Gopanel\CustomPermission;
use App\Models\Gopanel\CustomRole;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Artisan::call('config:clear');

        $permissionList = config('gopanel.permission_list');
        $allPermissions = [];

        foreach ($permissionList as $guard => $groups) {
            foreach ($groups as $group => $permissions) {
                foreach ($permissions as $permission) {
                    $p = CustomPermission::updateOrCreate(
                        ['name' => $permission['name'], 'guard_name' => $guard],
                        [
                            'title' => $permission['title'],
                            'group' => $group,
                        ]
                    );
                    $allPermissions[] = $p;
                }
            }
        }

        // Super Admin rolunu yarat və bütün icazələri ver
        $superRole = CustomRole::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'gopanel']
        );
        $superRole->syncPermissions($allPermissions);

        // İlk admini Super Admin roluna əlavə et
        $admin = Admin::query()->orderBy('id')->first();
        if ($admin && !$admin->hasRole('Super Admin')) {
            $admin->assignRole($superRole);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('İcazələr və Super Admin rolu uğurla yeniləndi.');
    }
}
