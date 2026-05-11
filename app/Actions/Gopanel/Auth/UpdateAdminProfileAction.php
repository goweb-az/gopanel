<?php

namespace App\Actions\Gopanel\Auth;

use App\Helpers\Gopanel\FileUploader;
use App\Models\Gopanel\Admin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAdminProfileAction
{
    use AsAction;

    public function handle(Admin $admin, string $fullName, string $email, ?UploadedFile $image = null): Admin
    {
        $data = [
            'full_name' => $fullName,
            'email' => $email,
        ];

        if ($image) {
            $data['image'] = FileUploader::toStorage(
                $image,
                'admins',
                'admin-'.$admin->id.'-'.time()
            );
        }

        $admin->update($data);

        Cache::forget("admin_avatar_{$admin->id}");

        return $admin->refresh();
    }
}
