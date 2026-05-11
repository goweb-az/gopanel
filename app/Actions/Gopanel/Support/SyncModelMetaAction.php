<?php

namespace App\Actions\Gopanel\Support;

use App\Models\Seo\PageMetaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;

class SyncModelMetaAction
{
    use AsAction;

    public function handle(Model $model, array $meta, array $uploads = []): void
    {
        foreach ($meta as $locale => $fields) {
            $title       = $fields['title'] ?? '';
            $description = $fields['description'] ?? '';
            $keywords    = $fields['keywords'] ?? '';
            $image       = $fields['image'] ?? '';

            $upload = $uploads[$locale] ?? null;
            if ($upload instanceof UploadedFile) {
                $image = StorePublicUploadAction::run(
                    upload: $upload,
                    folder: 'meta',
                    filename: "meta-{$model->getKey()}-{$locale}",
                );
            }

            if ($title === '' && $description === '' && $keywords === '' && $image === '') {
                PageMetaData::where('model_type', $model->getMorphClass())
                    ->where('model_id', $model->getKey())
                    ->where('locale', $locale)
                    ->delete();
                continue;
            }

            PageMetaData::updateOrCreate(
                [
                    'model_type' => $model->getMorphClass(),
                    'model_id'   => $model->getKey(),
                    'locale'     => $locale,
                ],
                [
                    'title'       => $title,
                    'description' => $description,
                    'keywords'    => $keywords,
                    'image'       => $image,
                ]
            );
        }
    }
}
