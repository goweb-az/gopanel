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

    protected function translationRules(array $required, array $optional = []): array
    {
        $rules = [];
        $defaultLocale = Language::getDefaultCode(config('app.locale', 'az'));

        foreach (Language::getCachedAll() as $lang) {
            $isDefault = $lang->code === $defaultLocale;

            foreach ($required as $attr => $max) {
                $rules["translations.{$lang->code}.{$attr}"] = [
                    $isDefault ? 'required' : 'nullable',
                    'string',
                    "max:{$max}",
                ];
            }

            foreach ($optional as $attr => $max) {
                $rules["translations.{$lang->code}.{$attr}"] = [
                    'nullable',
                    'string',
                    "max:{$max}",
                ];
            }
        }

        return $rules;
    }

}
