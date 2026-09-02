<?php

namespace App\Helpers\Gopanel;

use App\Models\Geography\Language;
use Illuminate\Support\Str;

class TranslationHelper
{
    /**
     * Create translations for the given item.
     *
     * Köhnə imza saxlanılır - starter üzərində qurulmuş layihələr bu metodu
     * birbaşa `Request` ilə çağırır. İş `fromInput()`-a ötürülür.
     *
     * @param $item
     * @param $request
     * @return void
     */
    public static function create($item, $request)
    {
        self::fromInput($item, is_null($request) ? [] : (array) $request->all());
    }

    /**
     * Tərcümələri hazır massivdən yazır: `[sahə => [dil => dəyər]]`.
     *
     * NİYƏ `Request`-siz variant lazımdır:
     * Servis layeri `Request`-i görməməlidir (bax: .claude/rules/01-umumi.md § 1).
     * `ContentSaveService` tərcümələri DTO-dan alır və bura ötürür; həmçinin
     * bu forma seeder və console command-lərdən də çağırıla bilir.
     *
     * @param  array<string, array<string, mixed>>  $input
     */
    public static function fromInput($item, array $input): void
    {
        try {
            foreach (Language::all() as $lang) {
                self::process($item, $input, $lang);
            }
        } catch (\Exception $e) {
            // Handle exception if needed (but no logging here)
        }
    }

    /**
     * Process the translations for a specific language.
     *
     * @param $item
     * @param array $input
     * @param $lang
     * @return void
     */
    private static function process($item, array $input, $lang)
    {
        foreach ($item->translatedAttributes as $transAttribute) {
            $newValue = self::getTranslatedValue($item, $transAttribute, $lang, $input);

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
     * @param array $input
     * @return string|null
     */
    private static function getTranslatedValue($item, $transAttribute, $lang, array $input)
    {
        // Default translation value from input
        $newValue = $input[$transAttribute][$lang->code] ?? null;

        // Special handling for 'slug' attribute based on slug_key
        if (isset($item->slug_key) && $transAttribute == 'slug' && in_array($item?->slug_key, $item->translatedAttributes)) {
            $titleKey = $item?->slug_key;
            $titleValue = $input[$titleKey][$lang->code] ?? null;

            if (empty($input[$transAttribute][$lang->code])) {
                $newValue = null;
                if ($titleValue) {
                    $newValue = Str::slug($titleValue, "-", $lang->code);
                }
            } else {
                $newValue = $input[$transAttribute][$lang->code];
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
