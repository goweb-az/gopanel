<?php

namespace App\Actions\Gopanel\ContactInfo;

use App\Actions\Gopanel\Support\SyncModelTranslationsAction;
use App\Models\Contact\ContactInfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveContactInfoFormAction
{
    use AsAction;

    public function handle(array $form, array $translations = []): ContactInfo
    {
        return DB::transaction(function () use ($form, $translations): ContactInfo {
            $item = ContactInfo::findOrNew($form['id'] ?? null);
            $item->fill(collect($form)->except('id')->all());
            $item->save();

            SyncModelTranslationsAction::run($item, $translations);

            Cache::forget('contact_info');

            return $item;
        });
    }
}
