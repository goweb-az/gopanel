<?php

namespace App\Traits\Content;

use App\Models\Seo\PageMetaData;

trait MetaData
{
    // Deleting meta data when the model is deleted
    protected static function bootMeta()
    {
        static::deleting(function ($model) {
            $model->metaAll()->delete();
        });
    }

    // Retrieves meta data for a specific locale
    public function meta($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->morphOne(PageMetaData::class, 'model')->where('locale', $locale);
    }

    // Retrieves all meta data (including translations)
    public function metaAll()
    {
        return $this->morphMany(PageMetaData::class, 'model');
    }

    // Accessor for the 'meta' attribute, which fetches meta data for the model
    public function getMetaAttribute()
    {
        return $this->meta();
    }

    // Retrieves a specific meta translation attribute for a given locale
    public function getMeta($attribute, $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $metaData = $this->meta($locale)->first();
        return $metaData ? $metaData->$attribute : null;
    }

    public function getMetaImage($locale = null)
    {
        $imagePath = $this->getMeta('image', $locale);
        if (!empty($imagePath)) {
            if (file_exists(public_path($imagePath))) {
                return url($imagePath);
            }
            return $imagePath;
        }
        return null;
    }
}
