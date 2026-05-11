<?php

namespace App\Actions\Gopanel\Social;

use App\Enums\Common\SocialIconTypeEnum;
use App\Helpers\Gopanel\FileUploader;
use App\Models\Contact\Social;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveSocialFormAction
{
    use AsAction;

    public function handle(array $form, ?UploadedFile $upload = null): Social
    {
        return DB::transaction(function () use ($form, $upload): Social {
            $social = Social::findOrNew($form['id'] ?? null);

            if ($upload && ($form['icon_type'] ?? null) === SocialIconTypeEnum::Image->value) {
                $fileName = FileUploader::nameGenerate(['name' => $form['name'] ?? 'social'], 'social');
                $form['icon'] = FileUploader::toPublic(
                    $upload,
                    (new Social())->getTable(),
                    $fileName
                );
            }

            $social->fill(collect($form)->except('id')->all());
            $social->save();

            return $social;
        });
    }
}
