<?php

namespace App\Services\Site\Seo;

use App\Models\Geography\Language;
use App\Models\Translations\FieldTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AlternatesService
{

    public function compose(Request $request)
    {
        $currentLocale  = app()->getLocale();
        $alternates     = [];
        foreach (Language::getCachedAll() as $language) {
            // if ($currentLocale != $language->code)
            $alternates[$language->code] =  $language->switchLanguage();
        }
        // $alternates['x-default'] = $this->getXDefault($currentLocale);
        return [
            'canonical'     => URL::current(),
            'alternates'    => $alternates,
        ];
    }


    private function getXDefault($currentLocale)
    {
        return rtrim(URL::to($currentLocale == 'az' ? '/' : $currentLocale));
    }
}
