<?php

namespace App\Actions\Gopanel\Admin;

use App\Models\Gopanel\Admin;
use App\Models\Gopanel\CustomRole;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Replace the admin's role assignments with the single role identified by
 * $roleId, or detach all roles when $roleId is null. The Admins panel only
 * lets an admin hold one role at a time, so this is intentionally singular.
 */
class SyncAdminRoleAction
{
    use AsAction;

    public function handle(Admin $admin, ?int $roleId): void
    {
        if (is_null($roleId)) {
            $admin->syncRoles([]);

            return;
        }

        $role = CustomRole::find($roleId);
        if ($role) {
            $admin->syncRoles($role->name);
        }
    }
}
