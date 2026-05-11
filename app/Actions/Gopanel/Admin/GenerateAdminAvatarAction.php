<?php

namespace App\Actions\Gopanel\Admin;

use App\Models\Gopanel\Admin;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Fetch a placeholder avatar from ui-avatars.com and store it on the public disk.
 * Returns the relative storage path so the caller can persist it on the model.
 * Best-effort — returns null on any failure (network, write, etc.).
 */
class GenerateAdminAvatarAction
{
    use AsAction;

    public function handle(Admin $admin): ?string
    {
        try {
            $name = urlencode($admin->full_name ?? 'Admin');
            $url = "https://ui-avatars.com/api/?name={$name}&background=556ee6&color=fff&size=256&font-size=0.4&format=png";
            $contents = @file_get_contents($url);

            if (! $contents) {
                return null;
            }

            $path = 'admins/admin-'.$admin->id.'.png';
            Storage::disk(gopanel_disk())->put($path, $contents);

            return $path;
        } catch (\Exception $e) {
            return null;
        }
    }
}
