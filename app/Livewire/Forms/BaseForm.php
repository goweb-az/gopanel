<?php

namespace App\Livewire\Forms;

use App\Models\Geography\Language;
use App\Models\Seo\PageMetaData;
use Illuminate\Database\Eloquent\Model;
use Livewire\Form;

abstract class BaseForm extends Form
{
    /**
     * translations[locale][field] = value
     */
    public array $translations = [];

    /**
     * meta[locale] = ['title' => '', 'description' => '', 'keywords' => '', 'image' => '']
     */
    public array $meta = [];

    /**
     * metaUploads[locale] = Livewire UploadedFile|null
     */
    public array $metaUploads = [];

    protected function prepareMeta(Model $model): void
    {
        $this->meta = [];
        $this->metaUploads = [];

        $rows = $model->exists
            ? PageMetaData::where('model_type', $model->getMorphClass())
                ->where('model_id', $model->getKey())
                ->get()
                ->keyBy('locale')
            : collect();

        foreach (Language::getCachedAll() as $lang) {
            $row = $rows->get($lang->code);
            $this->meta[$lang->code] = [
                'title' => $row?->title ?? '',
                'description' => $row?->description ?? '',
                'keywords' => $row?->keywords ?? '',
                'image' => $row?->image ?? '',
            ];
            $this->metaUploads[$lang->code] = null;
        }
    }

    protected function metaRules(): array
    {
        $rules = [];
        foreach (Language::getCachedAll() as $lang) {
            $rules["meta.{$lang->code}.title"] = ['nullable', 'string', 'max:255'];
            $rules["meta.{$lang->code}.description"] = ['nullable', 'string', 'max:1000'];
            $rules["meta.{$lang->code}.keywords"] = ['nullable', 'string', 'max:500'];
            $rules["meta.{$lang->code}.image"] = ['nullable', 'string', 'max:500'];
            $rules["metaUploads.{$lang->code}"] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
        }

        return $rules;
    }

    protected function prepareTranslations(Model $model): void
    {
        $this->translations = [];

        if (! method_exists($model, 'translations')) {
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
