<?php

namespace App\Http\Controllers\Gopanel\Contact;


use App\Helpers\Gopanel\Site\PageMetaDataHelper;
use App\Helpers\Gopanel\TranslationHelper;
use App\Http\Controllers\GoPanelController;
use App\Models\Contact\ContactInfo;
use Exception;
use Illuminate\Http\Request;

class ContactInfoController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $item = ContactInfo::latest()->first() ?? new ContactInfo();
        return view("gopanel.pages.contact.contact_info.index", compact("item"));
    }



    public function save(ContactInfo $item, Request $request)
    {
        try {
            $message    = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
            $this->saveData($item, $request);
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    private function saveData($item, $request)
    {
        $data       = $request->except(['_token']);

        $item  = $this->crudHelper->saveInstance($item, $data);
        if (isset($item->id)) {
            TranslationHelper::create($item, $request);
        }
        return $item;
    }
}
