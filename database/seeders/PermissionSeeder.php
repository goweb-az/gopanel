<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gopanel\CustomPermission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission_list = config('gopanel.permission_list');

        foreach ($permission_list as $guard => $groups) {
            foreach ($groups as $group => $permissions) {
                foreach ($permissions as $permission) {
                    CustomPermission::updateOrCreate(
                        ['name' => $permission['name'], 'group' => $group],
                        [
                            'title' => $permission['title'],
                            'guard_name' => $guard,
                        ]
                    );
                }
            }
        }

        $this->command->info('İcazələr uğurla əlavə olundu.');
    }
}
