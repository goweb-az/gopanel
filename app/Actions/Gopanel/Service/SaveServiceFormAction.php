<?php

namespace App\Actions\Gopanel\Service;

use App\Actions\Gopanel\Support\SyncModelMetaAction;
use App\Actions\Gopanel\Support\SyncModelTranslationsAction;
use App\Enums\Common\SocialIconTypeEnum;
use App\Helpers\Gopanel\FileUploader;
use App\Models\Site\Service;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveServiceFormAction
{
    use AsAction;

    public function handle(
        array $form,
        ?UploadedFile $iconUpload = null,
        ?UploadedFile $imageUpload = null,
        array $translations = [],
        array $meta = [],
        array $metaUploads = [],
    ): Service {
        return DB::transaction(function () use ($form, $iconUpload, $imageUpload, $translations, $meta, $metaUploads): Service {
            $service = Service::findOrNew($form['id'] ?? null);
            $title   = $translations['az']['title'] ?? 'service';

            if ($iconUpload && ($form['icon_type'] ?? null) === SocialIconTypeEnum::Image->value) {
                $form['icon'] = FileUploader::toPublic(
                    $iconUpload,
                    (new Service())->getTable(),
                    FileUploader::nameGenerate(['title' => $title], 'service-icon'),
                );
            }

            if ($imageUpload) {
                $form['image'] = FileUploader::toPublic(
                    $imageUpload,
                    (new Service())->getTable(),
                    FileUploader::nameGenerate(['title' => $title], 'service'),
                );
            }

            $service->fill(collect($form)->except('id')->all());
            $service->save();

            SyncModelTranslationsAction::run($service, $translations);
            SyncModelMetaAction::run($service, $meta, $metaUploads);

            return $service;
        });
    }
}
