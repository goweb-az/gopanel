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
            return $this->resolveFieldTranslation($attribute, $locale);
        }

        if (is_null($locale)) {
            $locale = app()->getLocale();
        }

        // Aktiv dillər keşdən oxunur (Language::getCachedAll) - əvvəl HƏR tərcümə
        // atributuna girişdə `languages` cədvəlinə ayrıca sorğu gedirdi.
        $activeLanguages = Language::getCachedAll();

        if (!$activeLanguages->contains('code', $locale)) {
            // Dil aktiv deyilsə standart dil (getCachedAll `default`-u birinci qaytarır).
            // Qeyd: əvvəl burada mövcud olmayan `->locale` sütunu oxunurdu və nəticə
            // həmişə null olurdu - fallback heç vaxt işləmirdi.
            $locale = $activeLanguages->first()?->code;
        }

        if (is_null($locale)) {
            return null;
        }

        return $this->resolveFieldTranslation($attribute, $locale);
    }

    /**
     * Tərcüməni tapır. `translations` əlaqəsi yüklənibsə (bu trait-in global
     * scope-u onu həmişə `with()` edir) YADDAŞDA süzülür - N+1 sorğu olmur.
     *
     * Niyə: əvvəl əlaqə yüklənsə də `$this->translations()` builder-i ilə yeni
     * sorğu atılırdı - 20 sətirlik siyahıda 3 tərcümə sahəsi = 60 əlavə sorğu.
     * Yüklənməyibsə (məs. `withoutGlobalScopes()`) köhnə davranış: tək sorğu.
     */
    protected function resolveFieldTranslation($attribute, $locale)
    {
        if ($this->relationLoaded('translations')) {
            return $this->getRelation('translations')->first(
                fn ($t) => $t->key === $attribute && $t->locale === $locale
            )?->value;
        }

        return $this->translations()
            ->where('key', $attribute)
            ->where('locale', $locale)
            ->first()?->value;
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
