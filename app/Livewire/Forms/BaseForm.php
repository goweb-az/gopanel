<?php

namespace App\Livewire\Forms;

use App\Models\Geography\Language;
use Illuminate\Database\Eloquent\Model;
use Livewire\Form;

abstract class BaseForm extends Form
{
    /**
     * translations[locale][field] = value
     */
    public array $translations = [];

    /**
     * Hydrate $translations from the model's existing translation rows.
     * Called by setItem() in concrete form objects.
     */
    protected function prepareTranslations(Model $model): void
    {
        $this->translations = [];

        if (!method_exists($model, 'translations')) {
            return;
        }

        $attributes = property_exists($model, 'translatedAttributes')
            ? $model->translatedAttributes
            : [];

        foreach (Language::getCachedAll() as $lang) {
            foreach ($attributes as $attr) {
                $value = null;
                if ($model->exists) {
                    $row = $model->translations()
                        ->where('locale', $lang->code)
                        ->where('key', $attr)
                        ->first();
                    $value = $row?->value;
                }
                $this->translations[$lang->code][$attr] = $value ?? '';
            }
        }
    }

    /**
     * Persist translations[locale][field] back to the model.
     * Uses updateOrCreate per (locale, key).
     */
    protected function syncTranslations(Model $model): void
    {
        if (!method_exists($model, 'translations')) {
            return;
        }

        $attributes = property_exists($model, 'translatedAttributes')
            ? $model->translatedAttributes
            : [];

        foreach ($this->translations as $locale => $fields) {
            foreach ($fields as $key => $value) {
                if (!in_array($key, $attributes, true)) {
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
