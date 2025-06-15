<?php

namespace App\Helpers\Gopanel\Site;

use App\Models\Geography\Language;
use App\Models\Seo\PageMetaData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PageMetaDataHelper
{
    /**
     * Entry method for saving meta data.
     */
    public static function save($item, array $metaDataInput, array $metaFiles = []): void
    {
        foreach (self::prepareLocales() as $locale) {
            // Check if there is any data to save for the current locale
            if (!self::shouldSave($metaDataInput, $metaFiles, $locale)) {
                continue;
            }

            // Upload image if provided
            $imagePath = self::uploadImage($metaFiles, $locale, $item, $metaDataInput['title'][$locale] ?? null);

            // Create or update meta data for the item
            self::createMetaData($item, $metaDataInput, $imagePath, $locale);
        }
    }

    /**
     * Returns supported locales based on active languages in the database.
     */
    protected static function prepareLocales(): array
    {
        return Language::where('is_active', true)
            ->pluck('code')
            ->toArray();
    }

    /**
     * Checks if at least one field exists for the given locale.
     */
    protected static function shouldSave(array $metaInput, array $metaFiles, string $locale): bool
    {
        return
            !empty($metaInput['title'][$locale] ?? null) ||
            !empty($metaInput['description'][$locale] ?? null) ||
            !empty($metaInput['keywords'][$locale] ?? null) ||
            !empty($metaFiles['image'][$locale] ?? null);
    }

    /**
     * Handles file upload if present and returns the file path.
     */
    protected static function uploadImage(array $metaFiles, string $locale, $item, $title = null): ?string
    {
        /** @var UploadedFile|null $image */
        $image = $metaFiles['image'][$locale] ?? null;

        if ($image instanceof UploadedFile) {
            $table      = $item->getTable();
            $folder     = public_path("site/meta/{$table}");
            $imageName  = !empty($title) ? ("{$table}-") . Str::slug($title, '-', $locale) : uniqid($table . "-");
            $fileName   = "qrgate-{$imageName}." . $image->getClientOriginalExtension();

            if (!file_exists($folder))
                mkdir($folder, 0755, true);
            $image->move($folder, $fileName);
            return "site/meta/{$table}/" . $fileName;
        }

        return null;
    }


    /**
     * Saves a PageMetaData record for the given locale.
     */
    protected static function createMetaData($item, array $metaInput, ?string $imagePath, string $locale): void
    {
        // Prepare data to save, only including fields that are provided
        $data = [
            'model_type'  => get_class($item),
            'model_id'    => $item->id,
            'source'      => $item->getTable(),
            'locale'      => $locale,
        ];

        // Add fields if present in the input
        if (!empty($metaInput['title'][$locale] ?? null)) {
            $data['title'] = $metaInput['title'][$locale];
        }

        if (!empty($metaInput['description'][$locale] ?? null)) {
            $data['description'] = $metaInput['description'][$locale];
        }

        if (!empty($metaInput['keywords'][$locale] ?? null)) {
            $data['keywords'] = $metaInput['keywords'][$locale];
        }

        if (!empty($imagePath)) {
            $data['image'] = $imagePath;
        }

        // Only save if at least one field exists (avoiding empty metadata)
        if (count($data) > 4) { // Base fields: model_type, model_id, source, locale
            PageMetaData::updateOrCreate(
                [
                    'model_type' => $data['model_type'],
                    'model_id'   => $data['model_id'],
                    'locale'     => $data['locale'],
                ],
                $data
            );
        }
    }
}
