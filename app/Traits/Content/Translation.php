<?php

namespace App\Traits\Content;

use App\Models\Geography\Language;
use App\Models\Translations\FieldTranslation;
use Illuminate\Database\Eloquent\Builder;

trait Translation
{


    protected static function bootTranslation()
    {
        static::addGlobalScope('translations', function (Builder $builder) {
            $model = $builder->getModel();
            if (property_exists($model, 'translatedAttributes') && !empty($model->translatedAttributes)) {
                $builder->with('translations');
            }
        });

        static::deleting(function ($model) {
            $model->translations()->delete();
        });
    }

    public function translations()
    {
        return $this->morphMany(FieldTranslation::class, 'model');
    }

    public function getTranslation($attribute, $locale = null, $fallback = false)
    {

        if ($fallback == true) {
            $translation = $this->translations()?->where('key', $attribute)?->where('locale', $locale)?->first();
            return $translation ? $translation?->value : null;
        }

        $language = null;
        if (is_null($locale)) {
            $locale = app()->getLocale();
        }

        $language = Language::where("is_active", 1)->where('code', $locale)->first();

        if (is_null($language)) {
            $locale = Language::where("is_active", 1)->first()?->locale;
        }

        if (is_null($locale))
            return null;

        $translation = $this->translations()?->where('key', $attribute)?->where('locale', $locale)?->first();
        return $translation ? $translation?->value : null;
    }


    public function translateOrDefault($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        $translatedModel = new static();

        foreach ($this->translatedAttributes as $attribute) {
            $translatedModel->$attribute = $this->getTranslation($attribute, $locale, true);
        }

        return $translatedModel;
    }

    public function toArray()
    {
        $array = parent::toArray();

        if (property_exists($this, 'translatedAttributes')) {
            foreach ($this->translatedAttributes as $attribute) {
                $array[$attribute] = $this->$attribute;
            }
        }

        return $array;
    }

    public function getAttribute($key)
    {
        if (property_exists($this, 'translatedAttributes') && in_array($key, $this->translatedAttributes)) {
            return $this->getTranslation($key);
        }
        return parent::getAttribute($key);
    }


    public function __get($key)
    {
        if (property_exists($this, 'translatedAttributes') && in_array($key, $this->translatedAttributes)) {
            return $this->getTranslation($key);
        }
        return parent::__get($key);
    }
}
