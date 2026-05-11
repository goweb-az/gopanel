<?php

namespace App\Actions\Gopanel\Slider;

use App\Actions\Gopanel\Support\SyncModelTranslationsAction;
use App\Helpers\Gopanel\FileUploader;
use App\Models\Site\Slider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveSliderFormAction
{
    use AsAction;

    /**
     * @param  array{id:?int,link:string,image:string,is_active:bool,sort_order:int}  $form
     * @param  UploadedFile|null  $upload  Newly uploaded slider image (replaces $form['image'] when present)
     * @param  array<string, array<string, string>>  $translations  [locale => [field => value]]
     */
    public function handle(array $form, ?UploadedFile $upload = null, array $translations = []): Slider
    {
        return DB::transaction(function () use ($form, $upload, $translations): Slider {
            $slider = Slider::findOrNew($form['id'] ?? null);

            if ($upload) {
                $fileName = FileUploader::nameGenerate(
                    ['title' => $translations['az']['title'] ?? 'slider'],
                    'slider'
                );
                $form['image'] = FileUploader::toPublic(
                    $upload,
                    (new Slider())->getTable(),
                    $fileName
                );
            }

            $slider->fill(collect($form)->except('id')->all());
            $slider->save();

            SyncModelTranslationsAction::run($slider, $translations);

            return $slider;
        });
    }
}
