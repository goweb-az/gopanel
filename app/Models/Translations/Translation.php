<?php

namespace App\Models\Translations;

use App\Enums\Gopanel\TranslationPlatfroms;
use App\Models\BaseModel;
use App\Models\Geography\Language;
use App\Traits\Ui\UiElements;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Translation extends BaseModel
{
    use HasFactory, SoftDeletes, UiElements;

    private $languages;

    protected $logEnabled = true;

    public function __construct()
    {
        parent::__construct();
        $this->languages = Language::all();
    }

    protected $fillable = [
        'key',
        'locale',
        'value',
        'group',
        'platform',
        'page',
        'filename',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->platform)) {
                $model->platform = TranslationPlatfroms::WEBSITE;
            }
            if (empty($model->filename)) {
                $model->filename = $model->platform;
            }
            if (empty($model->page)) {
                $model->page = 'general';
            }
        });

        static::saved(function ($translation) {
            $translation->writeToLangFile();
            $translation->writeToJsonFile();
        });

        static::deleted(function ($translation) {

            self::where('key', $translation->key)
                ->where('platform', $translation->platform)
                ->where('filename', $translation->filename)
                ->delete();

            $translation->removeFromLangFile();
            $translation->removeFromJsonFile();
        });
    }


    public function getValue($locale)
    {
        return static::where('key', $this->key)
            ->where('platform', $this->platform)
            ->where('filename', $this->filename)
            ->where('page', $this->page)
            ->where('locale', $locale)
            ->first()?->value ?? null;
    }

    public function getLangCheckExistsAttribute()
    {
        // NOTE: this still issues one exists() query per language per row (N+1).
        // A bounded fix would precompute existing (key,platform,filename,page,locale)
        // tuples for the current result page in a single query and look them up here,
        // but that requires plumbing the current page's row set into the model/accessor,
        // which the datatable's per-row rendering doesn't currently expose. Left as-is
        // to avoid a larger architectural change to GopanelDatatable for this task.
        $result = '';
        foreach ($this->languages as $language) {
            $check = $this->checkExists($language->code, $this->key, $this->filename);
            $icon  = $check ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
            $result .= "{$language->code} {$icon} ";
        }
        return $result;
    }

    public function checkExists($code, $key, $filename)
    {
        return self::where("locale", $code)
            ->where('key', $key)
            ->where('filename', $filename)
            ->where('page', $this->page)
            ->whereNotNull("value")
            ->exists();
    }


    public function getEditableValueAttribute()
    {
        return $this->double_click_edit("value");
    }


    /**
     * Regenerate both the PHP lang file and the JSON lang file for a given
     * locale/filename/platform combination from the current DB state.
     *
     * Extracted out of the per-instance writeToLangFile()/writeToJsonFile()
     * methods so bulk operations (e.g. TranslationBulkImportService) can
     * regenerate files once per (locale, filename) pair after a chunked
     * upsert, instead of looping Eloquent save() and triggering the model
     * events for every single row.
     */
    public static function regenerateFiles(string $locale, string $filename, string $platform): void
    {
        static::writeLangFileFor($locale, $filename, $platform);
        static::writeJsonFileFor($locale, $filename, $platform);
    }

    private static function writeLangFileFor(string $locale, string $filename, string $platform): void
    {
        $path = resource_path("lang/{$locale}/{$filename}.php");
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $allTranslations = static::where('locale', $locale)
            ->where('filename', $filename)
            ->where('platform', $platform)
            ->whereNotNull('value')
            ->get();

        $translations = [];

        foreach ($allTranslations as $translation) {
            $page = $translation->page ?: 'general';

            if ($translation->group) {
                $translations[$page][$translation->group][$translation->key] = $translation->value;
            } else {
                $translations[$page][$translation->key] = $translation->value;
            }
        }

        file_put_contents($path, '<?php return ' . var_export($translations, true) . ';');
    }

    private static function writeJsonFileFor(string $locale, string $filename, string $platform): void
    {
        $path = resource_path("lang-json/{$locale}/{$filename}.json");
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $allTranslations = static::where('locale', $locale)
            ->where('filename', $filename)
            ->where('platform', $platform)
            ->whereNotNull('value')
            ->get();

        $translations = [];

        foreach ($allTranslations as $translation) {
            $page = $translation->page ?: 'general';

            if ($translation->group) {
                $dotKey = $page . '.' . $translation->group . '.' . $translation->key;
            } else {
                $dotKey = $page . '.' . $translation->key;
            }

            $translations[$dotKey] = $translation->value;
        }

        file_put_contents(
            $path,
            json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function writeToLangFile()
    {
        static::writeLangFileFor($this->locale, $this->filename, $this->platform);
    }


    private function removeFromLangFile()
    {
        $path = resource_path("lang/{$this->locale}/{$this->filename}.php");
        if (!file_exists($path)) {
            return;
        }

        $translations = include($path);
        $page = $this->page ?: 'general';

        if ($this->group && isset($translations[$page][$this->group][$this->key])) {
            unset($translations[$page][$this->group][$this->key]);

            if (empty($translations[$page][$this->group])) {
                unset($translations[$page][$this->group]);
            }
        } else {
            unset($translations[$page][$this->key]);
        }

        if (isset($translations[$page]) && empty($translations[$page])) {
            unset($translations[$page]);
        }

        file_put_contents($path, '<?php return ' . var_export($translations, true) . ';');
    }



    private function writeToJsonFile()
    {
        static::writeJsonFileFor($this->locale, $this->filename, $this->platform);
    }


    private function removeFromJsonFile()
    {
        $path = resource_path("lang-json/{$this->locale}/{$this->filename}.json");

        if (!file_exists($path)) {
            return;
        }

        $translations = json_decode(file_get_contents($path), true);
        $page = $this->page ?: 'general';

        if ($this->group) {
            $dotKey = $page . '.' . $this->group . '.' . $this->key;
        } else {
            $dotKey = $page . '.' . $this->key;
        }

        unset($translations[$dotKey]);

        file_put_contents(
            $path,
            json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
