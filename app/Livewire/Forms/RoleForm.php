<?php

namespace App\Livewire\Forms;

use App\Models\Gopanel\CustomRole;
use Illuminate\Support\Facades\Auth;
use Livewire\Form;

class RoleForm extends Form
{
    public array $form = [
        'id'         => null,
        'name'       => '',
        'guard_name' => 'gopanel',
    ];

    /** @var string[] */
    public array $permissions = [];

    protected function rules(): array
    {
        $id = $this->form['id'] ?? 'NULL';

        return [
            'form.name'       => "required|string|max:120|unique:roles,name,{$id}",
            'form.guard_name' => 'required|string|in:gopanel,web,api',
            'permissions'     => 'array',
            'permissions.*'   => 'string|exists:permissions,name',
        ];
    }

    public function setItem(CustomRole $role): void
    {
        $this->form = [
            'id'         => $role->id,
            'name'       => $role->name ?? '',
            'guard_name' => $role->guard_name ?? 'gopanel',
        ];

        $this->permissions = $role->exists
            ? $role->permissions->pluck('name')->all()
            : [];
    }

    public function save(): CustomRole
    {
        $role = CustomRole::findOrNew($this->form['id']);

        $role->fill(collect($this->form)->except('id')->all());
        $role->save();

        $oldPermissions = $role->permissions->pluck('name')->toArray();
        $role->syncPermissions($this->permissions);
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

        $this->form['id'] = $role->id;

        return $role;
    }
}
