<?php

namespace App\Actions\Gopanel\Support;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Persist a translations[locale][field] map onto a model that uses the
 * Translation trait. Idempotent — uses updateOrCreate per (locale, key).
 */
class SyncModelTranslationsAction
{
    use AsAction;

    public function handle(Model $model, array $translations): void
    {
        if (! method_exists($model, 'translations')) {
            return;
        }

        $attributes = property_exists($model, 'translatedAttributes')
            ? $model->translatedAttributes
            : [];

        foreach ($translations as $locale => $fields) {
            foreach ($fields as $key => $value) {
                if (! in_array($key, $attributes, true)) {
                    continue;
                }
                $model->translations()->updateOrCreate(
                    ['locale' => $locale, 'key' => $key],
                    ['value' => $value]
                );
            }
        }
    }
}
