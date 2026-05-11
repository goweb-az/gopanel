<?php

namespace App\Actions\Gopanel\Role;

use App\Models\Gopanel\CustomRole;
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

            SyncRolePermissionsAction::run($role, $permissions);

            return $role;
        });
    }
}
