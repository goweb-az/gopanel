<?php

namespace App\Actions\Gopanel\Support;

use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Persist an uploaded file to gopanel_disk() under the given folder and return
 * the resulting relative path. Caller decides where to record the path.
 */
class StorePublicUploadAction
{
    use AsAction;

    public function handle(UploadedFile $upload, string $folder, string $filename): string
    {
        $ext = strtolower($upload->getClientOriginalExtension() ?: 'jpg');
        $name = "{$filename}.{$ext}";

        $upload->storePubliclyAs($folder, $name, ['disk' => gopanel_disk()]);

        return "{$folder}/{$name}";
    }
}
