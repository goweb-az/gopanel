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
        self::fromInputWithChanges($item, $input);
    }

    /**
     * `create()` ilə eyni, amma dəyişən tərcümələri qaytarır (bax: `fromInputWithChanges()`).
     *
     * @return array<string, array<string, array{old: ?string, new: ?string}>>
     */
    public static function createWithChanges($item, $request): array
    {
        return self::fromInputWithChanges($item, is_null($request) ? [] : (array) $request->all());
    }

    /**
     * Tərcümələri yazır və DƏYİŞƏNLƏRİ qaytarır: `sahə => dil => ['old' => .., 'new' => ..]`.
     *
     * Niyə: tərcümə sahələri `field_translations` cədvəlindədir, modelin öz
     * sütunu deyil - activity log-un `logOnlyDirty()` mexanizmi onları görmür.
     * «Adını dəyişdirdi: Köhnə → Yeni» cümləsi yalnız bu massivdən qurula bilər
     * (`App\Support\Activity\TranslationChanges::describe()`).
     *
     * Yalnız AKTİV dillər yazılır: panel forması yalnız aktiv dilləri göstərir
     * (`ViewServiceProvider` → `languages`). `Language::all()` gəzilsə, müvəqqəti
     * söndürülmüş dilin mövcud tərcüməsi hər saxlamada `null` ilə silinirdi.
     *
     * Köhnə dəyərlər BİR sorğu ilə oxunur (hər sahə/dil üçün ayrıca SELECT yox).
     *
     * @param  array<string, array<string, mixed>>  $input
     * @return array<string, array<string, array{old: ?string, new: ?string}>>
     */
    public static function fromInputWithChanges($item, array $input): array
    {
        $changes = [];

        try {
            $existing = self::existingValues($item);

            foreach (Language::query()->where('is_active', true)->get() as $lang) {
                $changes = array_merge_recursive($changes, self::process($item, $input, $lang, $existing));
            }
        } catch (\Exception $e) {
            // Handle exception if needed (but no logging here)
        }

        self::forgetLoadedTranslations($item);

        return $changes;
    }

    /**
     * Mövcud tərcümələr `"dil.sahə" => dəyər` şəklində.
     *
     * @return array<string, mixed>
     */
    private static function existingValues($item): array
    {
        if (!$item->exists) {
            return [];
        }

        return $item->translations()
            ->get(['locale', 'key', 'value'])
            ->mapWithKeys(fn ($row) => [$row->locale . '.' . $row->key => $row->value])
            ->all();
    }

    /**
     * Yaddaşdakı `translations` əlaqəsini atır.
     *
     * Niyə: `Translation` trait-i tərcüməni yüklənmiş əlaqədən oxuyur (N+1 olmasın).
     * Yazıdan sonra əlaqə atılmasa eyni sorğuda `$item->title` KÖHNƏ dəyəri
     * qaytarır (məs. save cavabındakı mesaj). Növbəti oxu təzə sorğu ilə gəlir.
     */
    private static function forgetLoadedTranslations($item): void
    {
        if (is_object($item) && method_exists($item, 'unsetRelation')) {
            $item->unsetRelation('translations');
        }
    }

    /**
     * Process the translations for a specific language.
     *
     * @param $item
     * @param array $input
     * @param $lang
     * @param array<string, mixed> $existing  `"dil.sahə" => köhnə dəyər`
     * @return array<string, array<string, array{old: ?string, new: ?string}>>
     */
    private static function process($item, array $input, $lang, array $existing = []): array
    {
        $changes = [];

        foreach ($item->translatedAttributes as $transAttribute) {
            $newValue = self::getTranslatedValue($item, $transAttribute, $lang, $input);
            $oldValue = $existing[$lang->code . '.' . $transAttribute] ?? null;

            $item->translations()->updateOrCreate(
                ['locale' => $lang->code, 'key' => $transAttribute],
                ['value' => $newValue]
            );

            $old = is_scalar($oldValue) ? (string) $oldValue : null;
            $new = is_scalar($newValue) ? (string) $newValue : null;

            if ((string) $old !== (string) $new) {
                $changes[$transAttribute][$lang->code] = ['old' => $old, 'new' => $new];
            }
        }

        return $changes;
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


    /**
     * Bir sahənin tərcümələrini `[dil => dəyər]` massivindən yazır (seeder-lər).
     *
     * `fromInput()`-dan fərqli olaraq BÜTÜN dillər gəzilir: seeder söndürülmüş
     * dilin məzmununu da əvvəlcədən hazırlayır ki, dil aktivləşəndə boş qalmasın.
     */
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

        self::forgetLoadedTranslations($item);
    }
}
