<?php

namespace App\Models\Translations;

use App\Enums\Gopanel\TranslationPlatfroms;
use App\Models\BaseModel;
use App\Models\Geography\Language;
use App\Traits\UiElements;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Translation extends BaseModel
{
    use HasFactory, SoftDeletes, UiElements;

    private $languages;

    protected $logEnabled = false;

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
            ->where('locale', $locale)
            ->first()?->value ?? null;
    }

    public function getLangCheckExistsAttribute()
    {
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
            ->whereNotNull("value")
            ->exists();
    }


    public function getEditableValueAttribute()
    {
        return $this->double_click_edit("value");
    }


    private function writeToLangFile()
    {
        $path = resource_path("lang/{$this->locale}/{$this->filename}.php");
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $allTranslations = static::where('locale', $this->locale)
            ->where('filename', $this->filename)
            ->where('platform', $this->platform)
            ->whereNotNull('value')
            ->get();

        $translations = [];

        foreach ($allTranslations as $translation) {
            if ($translation->group) {
                $translations[$translation->group][$translation->key] = $translation->value;
            } else {
                $translations[$translation->key] = $translation->value;
            }
        }

        file_put_contents($path, '<?php return ' . var_export($translations, true) . ';');
    }


    private function removeFromLangFile()
    {
        $path = resource_path("lang/{$this->locale}/{$this->filename}.php");
        if (!file_exists($path)) {
            return;
        }

        $translations = include($path);

        if ($this->group && isset($translations[$this->group][$this->key])) {
            unset($translations[$this->group][$this->key]);

            if (empty($translations[$this->group])) {
                unset($translations[$this->group]);
            }
        } else {
            unset($translations[$this->key]);
        }

        file_put_contents($path, '<?php return ' . var_export($translations, true) . ';');
    }



    private function writeToJsonFile()
    {
        $path = resource_path("lang-json/{$this->locale}/{$this->filename}.json");
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $allTranslations = static::where('locale', $this->locale)
            ->where('filename', $this->filename)
            ->where('platform', $this->platform)
            ->whereNotNull('value')
            ->get();

        $translations = [];

        foreach ($allTranslations as $translation) {
            if ($translation->group) {
                $dotKey = $translation->group . '.' . $translation->key;
            } else {
                $dotKey = $translation->key;
            }

            $translations[$dotKey] = $translation->value;
        }

        file_put_contents(
            $path,
            json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }


    private function removeFromJsonFile()
    {
        $path = resource_path("lang-json/{$this->locale}/{$this->filename}.json");

        if (!file_exists($path)) {
            return;
        }

        $translations = json_decode(file_get_contents($path), true);

        if ($this->group) {
            $dotKey = $this->group . '.' . $this->key;
        } else {
            $dotKey = $this->key;
        }

        unset($translations[$dotKey]);

        file_put_contents(
            $path,
            json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
