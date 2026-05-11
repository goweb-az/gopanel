<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\Admin\SaveAdminFormAction;
use App\Models\Gopanel\Admin;
use Livewire\Form;

class AdminForm extends Form
{
    public array $form = [
        'id' => null,
        'full_name' => '',
        'email' => '',
        'image' => '',
        'is_active' => true,
        'is_super' => false,
        'role_id' => null,
    ];

    public string $password = '';

    public string $password_confirmation = '';

    public mixed $upload = null;

    protected function rules(): array
    {
        $id = $this->form['id'] ?? 'NULL';

        return [
            'form.full_name' => ['required', 'string', 'max:120'],
            'form.email' => ['required', 'email', 'max:160', "unique:admins,email,{$id}"],
            'form.is_active' => 'boolean',
            'form.is_super' => 'boolean',
            'form.role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'password' => $this->form['id']
                ? ['nullable', 'string', 'min:6', 'confirmed']
                : ['required', 'string', 'min:6', 'confirmed'],
            'upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function setItem(Admin $admin): void
    {
        $roleId = $admin->roles->first()?->id;

        $this->form = [
            'id' => $admin->id,
            'full_name' => $admin->full_name ?? '',
            'email' => $admin->email ?? '',
            'image' => $admin->image ?? '',
            'is_active' => (bool) ($admin->is_active ?? true),
            'is_super' => (bool) ($admin->is_super ?? false),
            'role_id' => $roleId,
        ];

        $this->password = '';
        $this->password_confirmation = '';
    }

    public function save(): Admin
    {
        $admin = SaveAdminFormAction::run(
            form: $this->form,
            password: $this->password,
            upload: $this->upload,
        );

        $this->form['id'] = $admin->id;
        $this->password = '';
        $this->password_confirmation = '';
        $this->upload = null;

        return $admin;
    }
}
