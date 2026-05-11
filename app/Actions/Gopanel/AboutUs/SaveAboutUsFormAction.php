<?php

namespace App\Actions\Gopanel\AboutUs;

use App\Actions\Gopanel\Support\SyncModelTranslationsAction;
use App\Helpers\Gopanel\FileUploader;
use App\Models\Site\AboutUs;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveAboutUsFormAction
{
    use AsAction;

    public function handle(array $form, ?UploadedFile $upload = null, array $translations = []): AboutUs
    {
        return DB::transaction(function () use ($form, $upload, $translations): AboutUs {
            $item = AboutUs::findOrNew($form['id'] ?? null);

            if ($upload) {
                $form['image'] = FileUploader::toPublic(
                    $upload,
                    (new AboutUs())->getTable(),
                    FileUploader::nameGenerate(['title' => $translations['az']['title'] ?? 'about-us'], 'about-us'),
                );
            }

            $item->fill(collect($form)->except('id')->all());
            $item->save();

            SyncModelTranslationsAction::run($item, $translations);

            return $item;
        });
    }
}
