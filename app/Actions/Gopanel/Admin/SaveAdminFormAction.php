<?php

namespace App\Actions\Gopanel\Admin;

use App\Helpers\Gopanel\FileUploader;
use App\Models\Gopanel\Admin;
use App\Models\Gopanel\CustomRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveAdminFormAction
{
    use AsAction;

    public function handle(
        array $form,
        ?string $password = null,
        ?UploadedFile $upload = null,
    ): Admin {
        return DB::transaction(function () use ($form, $password, $upload): Admin {
            $isCreate = empty($form['id']);
            $admin    = Admin::findOrNew($form['id'] ?? null);

            if ($upload) {
                $form['image'] = FileUploader::toStorage($upload, 'admins', 'admin-' . time());
            }

            $data = collect($form)->except(['id', 'role_id'])->all();

            if ($password !== null && $password !== '') {
                $data['password'] = Hash::make($password);
            }

            $admin->fill($data);
            $admin->save();

            if ($isCreate && empty($admin->image)) {
                $generated = $this->generateAvatar($admin);
                if ($generated) {
                    $admin->image = $generated;
                    $admin->save();
                }
            }

            $roleId = $form['role_id'] ?? null;
            if ($roleId) {
                $role = CustomRole::find($roleId);
                if ($role) {
                    $admin->syncRoles($role->name);
                }
            } else {
                $admin->syncRoles([]);
            }

            return $admin;
        });
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
