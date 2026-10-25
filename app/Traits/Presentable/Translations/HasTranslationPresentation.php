<?php

namespace App\Traits\Presentable\Translations;

/**
 * `Translation` (tərcümə açarı) modelinin siyahı hüceyrələri.
 *
 * Niyə: model fayl generasiyası (lang/*.php, lang-json) ilə dolu idi; panel
 * hüceyrələri bura ayrılıb. Adlar DƏYİŞMƏYİB - tərcümə datatable-ı işlədir.
 *
 * Qeyd (geriyə uyğunluq istisnası): `lang_check_exists` hər dil üçün modelin
 * `checkExists()` sorğusunu çağırır (N+1). Sorğu modeldə qalır; düzəlişi
 * texnotech-dəki kimi səhifə üçün əvvəlcədən doldurulmuş dil matrisidir
 * (TranslationQuery) - starter-də hələ yoxdur.
 */
trait HasTranslationPresentation
{
    public function getLangCheckExistsAttribute()
    {
        $result = '';
        foreach ($this->languages as $language) {
            $check = $this->checkExists($language->code, $this->key, $this->filename);
            $icon  = $check ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
            $result .= e($language->code) . " {$icon} ";
        }
        return $result;
    }

    /** İkiqat kliklə redaktə hüceyrəsi (`UiElements::double_click_edit`). */
    public function getEditableValueAttribute()
    {
        return $this->double_click_edit("value");
    }
}
