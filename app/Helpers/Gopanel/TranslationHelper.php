<?php

namespace App\Helpers\Gopanel;

use App\Models\Geography\Language;
use Illuminate\Support\Str;

class TranslationHelper
{
    /**
     * Create translations for the given item.
     *
     * @param $item
     * @param $request
     * @return void
     */
    public static function create($item, $request)
    {
        try {
            foreach (Language::all() as $lang) {
                self::process($item, $request, $lang);
            }
        } catch (\Exception $e) {
            // Handle exception if needed (but no logging here)
        }
    }

    /**
     * Process the translations for a specific language.
     *
     * @param $item
     * @param $request
     * @param $lang
     * @return void
     */
    private static function process($item, $request, $lang)
    {
        foreach ($item->translatedAttributes as $transAttribute) {
            $newValue = self::getTranslatedValue($item, $transAttribute, $lang, $request);

            $item->translations()->updateOrCreate(
                ['locale' => $lang->code, 'key' => $transAttribute],
                ['value' => $newValue]
            );
        }
    }

    /**
     * Get the translated value for a given attribute and language.
     *
     * @param $item
     * @param $transAttribute
     * @param $lang
     * @param $request
     * @return string|null
     */
    private static function getTranslatedValue($item, $transAttribute, $lang, $request)
    {
        // Default translation value from request
        $newValue = $request?->$transAttribute[$lang->code] ?? null;

        // Special handling for 'slug' attribute based on slug_key
        if (isset($item->slug_key) && $transAttribute == 'slug' && in_array($item?->slug_key, $item->translatedAttributes)) {
            $titleKey = $item?->slug_key;
            $titleValue = $request?->$titleKey[$lang->code] ?? null;
            $newValue = null;

            if ($titleValue) {
                $newValue = Str::slug($titleValue);
            }
        }

        return $newValue;
    }
}
