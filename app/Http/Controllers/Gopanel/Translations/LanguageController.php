<?php

namespace App\Http\Controllers\Gopanel\Translations;

use App\Http\Controllers\GoPanelController;
use App\Models\Geography\Country;
use App\Models\Geography\Language;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class LanguageController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $languagesList = Language::orderBy("sort_order", "ASC")->get();
        return view("gopanel.pages.languages.index", compact('languagesList'));
    }

    public function getForm(Language $item, Request $request)
    {
        try {
            $route = route("gopanel.languages.save", $item);
            $this->response['html'] = View::make('gopanel.pages.languages.partials.form', [
                'item'          => $item,
                'route'         => $route,
                'countries'     => Country::all()
            ])->render();
            $this->success_response([], "Form yaradıldı");
        } catch (Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function save(Language $item, Request $request)
    {
        try {
            $data       = $request->except(['_token']);
            $message    = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
            $item       = $this->crudHelper->saveInstance($item, $data);
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
