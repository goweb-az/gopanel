<?php

namespace App\Actions\Gopanel\SiteSetting;

use App\Actions\Gopanel\Support\SyncModelMetaAction;
use App\Helpers\Gopanel\FileUploader;
use App\Models\Settings\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveSiteSettingFormAction
{
    use AsAction;

    public function handle(
        array $form,
        ?UploadedFile $logoLightUpload = null,
        ?UploadedFile $logoDarkUpload = null,
        ?UploadedFile $mailLogoUpload = null,
        ?UploadedFile $gopanelLogoUpload = null,
        array $meta = [],
        array $metaUploads = [],
    ): SiteSetting {
        return DB::transaction(function () use ($form, $logoLightUpload, $logoDarkUpload, $mailLogoUpload, $gopanelLogoUpload, $meta, $metaUploads): SiteSetting {
            $item = SiteSetting::findOrNew($form['id'] ?? null);

            if ($logoLightUpload) {
                $form['logo_light'] = FileUploader::toPublic($logoLightUpload, 'site-logo', 'logo-light');
            }
            if ($logoDarkUpload) {
                $form['logo_dark'] = FileUploader::toPublic($logoDarkUpload, 'site-logo', 'logo-dark');
            }
            if ($mailLogoUpload) {
                $form['mail_logo'] = FileUploader::toPublic($mailLogoUpload, 'site-logo', 'mail-logo');
            }
            if ($gopanelLogoUpload) {
                $form['gopanel_logo'] = FileUploader::toPublic($gopanelLogoUpload, 'site-logo', 'gopanel-logo');
            }

            $item->fill(collect($form)->except('id')->all());
            $item->save();

            SyncModelMetaAction::run($item, $meta, $metaUploads);

            Cache::forget('site_settings'.app()->getLocale());

            return $item;
        });
    }
}
