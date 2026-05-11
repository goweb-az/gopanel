<?php

namespace App\Actions\Gopanel\Admin;

use App\Helpers\Gopanel\FileUploader;
use App\Models\Gopanel\Admin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Save an admin record. Orchestrates the discrete sub-actions:
 *   - file upload  → FileUploader helper (single statement, no Action wrapper)
 *   - role sync    → SyncAdminRoleAction
 *   - auto avatar  → GenerateAdminAvatarAction (create + no manual upload)
 *
 * Password hashing stays inline because it's a one-liner with no other behaviour.
 */
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
            $admin = Admin::findOrNew($form['id'] ?? null);

            if ($upload) {
                $form['image'] = FileUploader::toStorage($upload, 'admins', 'admin-'.time());
            }

            $data = collect($form)->except(['id', 'role_id'])->all();

            if (! is_null($password) && $password !== '') {
                $data['password'] = Hash::make($password);
            }

            $admin->fill($data);
            $admin->save();

            if ($isCreate && empty($admin->image)) {
                $generated = GenerateAdminAvatarAction::run($admin);
                if ($generated) {
                    $admin->image = $generated;
                    $admin->save();
                }
            }

            SyncAdminRoleAction::run($admin, $form['role_id'] ?? null);

            return $admin;
        });
    }
}
