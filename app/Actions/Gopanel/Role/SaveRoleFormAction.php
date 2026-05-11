<?php

namespace App\Actions\Gopanel\Role;

use App\Models\Gopanel\CustomRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveRoleFormAction
{
    use AsAction;

    /**
     * @param  array{id:?int,name:string,guard_name:string}  $form
     * @param  string[]  $permissions  Permission names to sync onto the role.
     */
    public function handle(array $form, array $permissions = []): CustomRole
    {
        return DB::transaction(function () use ($form, $permissions): CustomRole {
            $role = CustomRole::findOrNew($form['id'] ?? null);

            $role->fill(collect($form)->except('id')->all());
            $role->save();

            $oldPermissions = $role->permissions->pluck('name')->toArray();
            $role->syncPermissions($permissions);
            $newPermissions = $role->fresh()->permissions->pluck('name')->toArray();

            $added   = array_values(array_diff($newPermissions, $oldPermissions));
            $removed = array_values(array_diff($oldPermissions, $newPermissions));

            if (! empty($added) || ! empty($removed)) {
                activity()
                    ->performedOn($role)
                    ->causedBy(Auth::guard('gopanel')->user())
                    ->event('updated')
                    ->withProperties([
                        'old'        => ['permissions' => $oldPermissions],
                        'attributes' => ['permissions' => $newPermissions],
                        'added'      => $added,
                        'removed'    => $removed,
                    ])
                    ->useLog('CustomRole')
                    ->log(":causer «{$role->name}» vəzifəsinin icazələrini yenilədi (" . count($newPermissions) . ' icazə)');
            }

            return $role;
        });
    }
}
