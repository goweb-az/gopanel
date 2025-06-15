<?php

namespace App\Traits;

use App\Models\Seo\PageMetaData;

trait MetaData
{
    // Deleting meta data when the model is deleted
    protected static function bootMeta()
    {
        static::deleting(function ($model) {
            $model->metaAll()->delete(); // Deletes all meta data associated with the model
        });
    }

    // Retrieves meta data for a specific locale
    public function meta($locale = null)
    {
        $locale = $locale ?? app()->getLocale(); // Use the application's default locale if none is provided
        return $this->morphOne(PageMetaData::class, 'model')->where('locale', $locale); // Retrieve meta data for the specified locale
    }

    // Retrieves all meta data (including translations)
    public function metaAll()
    {
        return $this->morphMany(PageMetaData::class, 'model'); // Retrieves all meta data associated with the model
    }

    // Accessor for the 'meta' attribute, which fetches meta data for the model
    public function getMetaAttribute()
    {
        return $this->meta(); // Returns the meta data for the current model
    }

    // Retrieves a specific meta translation attribute for a given locale
    public function getMeta($attribute, $locale = null)
    {
        $locale = $locale ?? app()->getLocale(); // Use the application's default locale if none is provided

        // Fetch the meta data for the given locale
        $metaData = $this->meta($locale)->first();

        // Return the specific attribute from the meta translation if it exists
        return $metaData ? $metaData->$attribute : null;
    }

    public function getMetaImage($locale = null)
    {
        $imagePath = $this->getMeta('image', $locale);
        if (!empty($imagePath))
            return $this->getFileUrl($imagePath); // Return the full URL to the image (assuming it's stored in the public disk)
        return null; // If no image path is found, return null
    }


    /**
     * Faylin yolunu qaytar
     * 
     * @param  string  $file
     * @return string
     */
    public function getFileUrl($file)
    {
        if (file_exists(public_path($file)))
            return url($file);
        return $file;
    }
}
