<?php

namespace App\Actions\Gopanel\Category;

use App\Actions\Gopanel\Support\SyncModelMetaAction;
use App\Actions\Gopanel\Support\SyncModelTranslationsAction;
use App\Enums\Common\SocialIconTypeEnum;
use App\Helpers\Gopanel\FileUploader;
use App\Models\Navigation\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveCategoryFormAction
{
    use AsAction;

    public function handle(
        array $form,
        ?UploadedFile $iconUpload = null,
        array $translations = [],
        array $meta = [],
        array $metaUploads = [],
    ): Category {
        return DB::transaction(function () use ($form, $iconUpload, $translations, $meta, $metaUploads): Category {
            $category = Category::findOrNew($form['id'] ?? null);

            if ($iconUpload && ($form['icon_type'] ?? null) === SocialIconTypeEnum::Image->value) {
                $form['icon'] = FileUploader::toPublic(
                    $iconUpload,
                    (new Category)->getTable(),
                    FileUploader::nameGenerate(['name' => $translations['az']['name'] ?? 'category'], 'category-icon'),
                );
            }

            $category->fill(collect($form)->except('id')->all());
            $category->save();

            SyncModelTranslationsAction::run($category, $translations);
            SyncModelMetaAction::run($category, $meta, $metaUploads);

            return $category;
        });
    }
}
