<?php

namespace App\Http\Controllers\Gopanel\Contact;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Contact\ContactInfoSaveRequest;
use App\Models\Contact\ContactInfo;
use App\Queries\Gopanel\Common\SingleRecordQuery;
use App\Services\Gopanel\Content\ContentSaveService;
use Illuminate\Http\Request;

class ContactInfoController extends GoPanelController
{
    public function __construct(private readonly ContentSaveService $content)
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $item = (new SingleRecordQuery(ContactInfo::class))->currentOrNew();

        return view("gopanel.pages.contact.contact_info.index", compact("item"));
    }


    public function save(ContactInfo $item, ContactInfoSaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";

            $item = $this->content->save($item, $request->payload());

            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
