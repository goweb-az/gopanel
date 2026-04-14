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

            if (empty($request?->$transAttribute[$lang->code])) {
                $newValue = null;
                if ($titleValue) {
                    $newValue = Str::slug($titleValue, "-", $lang->code);
                }
            } else {
                $newValue = $request?->$transAttribute[$lang->code];
            }

            if (!empty($item->slug_prefix[$lang->code]) && !empty($newValue)) {
                $prefix = Str::slug($item->slug_prefix[$lang->code], '-', $lang->code);
                if (!Str::startsWith($newValue, $prefix)) {
                    $newValue = $prefix . '-' . Str::slug($newValue, '-', $lang->code);
                }
            }
        }

        return $newValue;
    }


    public static function basic($item, $data, $transAttribute)
    {
        foreach (Language::all() as $lang) {

            $newValue = $data[$lang->code] ?? null;
            if (in_array($transAttribute, $item->translatedAttributes)) {
                $item->translations()->updateOrCreate(
                    ['locale' => $lang->code, 'key' => $transAttribute],
                    ['value' => $newValue]
                );
            }
        }
    }
}
