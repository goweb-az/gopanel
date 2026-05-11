<?php

namespace App\Actions\Gopanel\Role;

use App\Models\Gopanel\CustomRole;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Replace the role's permission set with the given list. If the diff is
 * non-empty, also write a manual activity log entry that captures the
 * before/after permission names and the added/removed deltas.
 *
 * @param  string[]  $permissions
 */
class SyncRolePermissionsAction
{
    use AsAction;

    public function handle(CustomRole $role, array $permissions): void
    {
        $oldPermissions = $role->permissions->pluck('name')->toArray();
        $role->syncPermissions($permissions);
        $newPermissions = $role->fresh()->permissions->pluck('name')->toArray();

        $added = array_values(array_diff($newPermissions, $oldPermissions));
        $removed = array_values(array_diff($oldPermissions, $newPermissions));

        if (empty($added) && empty($removed)) {
            return;
        }

        activity()
            ->performedOn($role)
            ->causedBy(Auth::guard('gopanel')->user())
            ->event('updated')
            ->withProperties([
                'old' => ['permissions' => $oldPermissions],
                'attributes' => ['permissions' => $newPermissions],
                'added' => $added,
                'removed' => $removed,
            ])
            ->useLog('CustomRole')
            ->log(":causer «{$role->name}» vəzifəsinin icazələrini yenilədi (".count($newPermissions).' icazə)');
    }
}
