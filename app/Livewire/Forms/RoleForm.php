<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\Role\SaveRoleFormAction;
use App\Models\Gopanel\CustomRole;
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
            'form.name'       => ['required', 'string', 'max:120', "unique:roles,name,{$id}"],
            'form.guard_name' => ['required', 'string', 'in:gopanel,web,api'],
            'permissions'     => 'array',
            'permissions.*'   => ['string', 'exists:permissions,name'],
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
        $role = SaveRoleFormAction::run(
            form: $this->form,
            permissions: $this->permissions,
        );

        $this->form['id'] = $role->id;

        return $role;
    }
}
