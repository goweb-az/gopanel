<?php

namespace App\Actions\Gopanel\Product;

use App\Actions\Gopanel\Support\SyncModelMetaAction;
use App\Actions\Gopanel\Support\SyncModelTranslationsAction;
use App\Helpers\Gopanel\FileUploader;
use App\Models\Site\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveProductFormAction
{
    use AsAction;

    public function handle(
        array $form,
        ?UploadedFile $upload = null,
        array $translations = [],
        array $meta = [],
        array $metaUploads = [],
    ): Product {
        return DB::transaction(function () use ($form, $upload, $translations, $meta, $metaUploads): Product {
            $product = Product::findOrNew($form['id'] ?? null);

            if ($upload) {
                $form['image'] = FileUploader::toPublic(
                    $upload,
                    (new Product)->getTable(),
                    FileUploader::nameGenerate(['title' => $translations['az']['title'] ?? 'product'], 'product'),
                );
            }

            $product->fill(collect($form)->except('id')->all());
            $product->save();

            SyncModelTranslationsAction::run($product, $translations);
            SyncModelMetaAction::run($product, $meta, $metaUploads);

            return $product;
        });
    }
}
