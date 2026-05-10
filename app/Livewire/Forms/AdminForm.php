<?php

namespace App\Livewire\Forms;

use App\Helpers\Gopanel\FileUploader;
use App\Models\Gopanel\Admin;
use App\Models\Gopanel\CustomRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Form;

class AdminForm extends Form
{
    public array $form = [
        'id'        => null,
        'full_name' => '',
        'email'     => '',
        'image'     => '',
        'is_active' => true,
        'is_super'  => false,
        'role_id'   => null,
    ];

    public string $password = '';
    public string $password_confirmation = '';

    public mixed $upload = null;

    protected function rules(): array
    {
        $id = $this->form['id'] ?? 'NULL';

        return [
            'form.full_name' => 'required|string|max:120',
            'form.email'     => "required|email|max:160|unique:admins,email,{$id}",
            'form.is_active' => 'boolean',
            'form.is_super'  => 'boolean',
            'form.role_id'   => 'nullable|integer|exists:roles,id',
            'password'       => $this->form['id'] ? 'nullable|string|min:6|confirmed' : 'required|string|min:6|confirmed',
            'upload'         => 'nullable|image|max:2048',
        ];
    }

    public function setItem(Admin $admin): void
    {
        $roleId = $admin->roles->first()?->id;

        $this->form = [
            'id'        => $admin->id,
            'full_name' => $admin->full_name ?? '',
            'email'     => $admin->email ?? '',
            'image'     => $admin->image ?? '',
            'is_active' => (bool) ($admin->is_active ?? true),
            'is_super'  => (bool) ($admin->is_super ?? false),
            'role_id'   => $roleId,
        ];

        $this->password = '';
        $this->password_confirmation = '';
    }

    public function save(): Admin
    {
        $admin = Admin::findOrNew($this->form['id']);

        if ($this->upload) {
            $this->form['image'] = FileUploader::toStorage(
                $this->upload,
                'admins',
                'admin-' . time()
            );
        }

        $data = collect($this->form)->except(['id', 'role_id'])->all();

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        $admin->fill($data);
        $admin->save();

        if (! $this->form['id'] && empty($admin->image)) {
            $generated = $this->generateAvatar($admin);
            if ($generated) {
                $admin->image = $generated;
                $admin->save();
            }
        }

        if ($this->form['role_id']) {
            $role = CustomRole::find($this->form['role_id']);
            if ($role) {
                $admin->syncRoles($role->name);
            }
        } else {
            $admin->syncRoles([]);
        }

        $this->form['id'] = $admin->id;
        $this->password = '';
        $this->password_confirmation = '';
        $this->upload = null;

        return $admin;
    }

    private function generateAvatar(Admin $admin): ?string
    {
        try {
            $name = urlencode($admin->full_name ?? 'Admin');
            $url = "https://ui-avatars.com/api/?name={$name}&background=556ee6&color=fff&size=256&font-size=0.4&format=png";
            $contents = @file_get_contents($url);

            if ($contents) {
                $folder = 'admins';
                $filename = 'admin-' . $admin->id . '.png';
                Storage::disk('public')->put($folder . '/' . $filename, $contents);
                return "{$folder}/{$filename}";
            }
        } catch (\Exception $e) {
            //
        }

        return null;
    }
}
