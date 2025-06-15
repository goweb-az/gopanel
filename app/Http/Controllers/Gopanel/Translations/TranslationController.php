<?php

namespace App\Http\Controllers\Gopanel\Translations;

use App\Enums\Gopanel\TranslationGroups;
use App\Enums\Gopanel\TranslationPlatfroms;
use App\Http\Controllers\GoPanelController;
use App\Models\Translations\Translation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class TranslationController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $items      = Translation::all();
        $locale     = $request->has("locale") ? $request->input("locale") : app()->getLocale();
        return view("gopanel.pages.translations.index", compact("items", 'locale'));
    }




    public function getForm(Translation $item, Request $request)
    {
        try {
            $route = route("gopanel.translations.save.form", $item);
            $this->response['html'] = View::make('gopanel.pages.translations.partials.form', [
                'item'              => $item,
                'route'             => $route,
                'platforms'         => TranslationPlatfroms::cases(),
                'groups'            => TranslationGroups::cases(),
                'selectedExists'    => false
            ])->render();
            $this->success_response([], "Form yaradıldı");
        } catch (Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function save(Translation $item, Request $request)
    {
        try {
            $data = $request->except(['_token']);
            if (!is_null($item)) {
                $message    = "Məlumat uğurla dəyişdirildi!";
            } else {
                $item       = new Translation();
                $message    = "Məlumat uğurla yaradıldı!";
            }
            foreach ($data['value'] as $locale => $value) {
                $this->saveData($item, $data, $locale, $value);
            }
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }



    private function saveData($item, $data, $locale, $value)
    {
        $existingItem = Translation::where('key', $data['key'] ?? $item->key)
            ->where('platform', $data['platform'] ?? $item->platform)
            ->where('locale', $locale)
            ->first();

        if ($existingItem) {
            $existingItem->value    = $value;
            $existingItem->key      = $data['key'] ?? $existingItem->key;
            $existingItem->platform = $data['platform'] ?? $existingItem->platform;
            $existingItem->filename = $data['filename'] ?? $existingItem->filename;
            $existingItem->group    = $data['group'] ?? $existingItem->group;
            $existingItem->save();
        } else {
            $newItem = new Translation();
            $newItem->key       = $data['key'];
            $newItem->platform  = $data['platform'];
            $newItem->filename  = $data['filename'] ?? $data['platform'];
            $newItem->group     = $data['group'] ?? $data['group'];
            $newItem->locale    = $locale;
            $newItem->value     = $value;
            $newItem->save();
        }
    }
}
