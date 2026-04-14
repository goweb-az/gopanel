<?php

namespace App\Http\Controllers\Gopanel\Contact;

use App\Enums\Common\SocialIconTypeEnum;
use App\Http\Controllers\GoPanelController;
use App\Models\Contact\Social;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class SocialController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $socials = Social::all();
        return view("gopanel.pages.contact.socials.index", compact('socials'));
    }

    public function getForm(Social $item, Request $request)
    {
        try {
            $route = route("gopanel.contact.socials.save", $item);
            $this->response['html'] = View::make('gopanel.pages.contact.socials.partials.form', [
                'item'          => $item,
                'route'         => $route,
                'types'         => SocialIconTypeEnum::cases()
            ])->render();
            $this->success_response([], "Form yaradıldı");
        } catch (Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function save(Social $item, Request $request)
    {
        try {
            $message    = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
            $item       = $this->saveData($item, $request);
            $this->response['redirect'] = route("gopanel.contact.socials.index");
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    private function saveData($item, $request)
    {
        $data       = $request->except(['_token']);

        if ($request->hasFile("image")) {
            $file               = $request->file('image');
            $fileName           = Str::slug($request->name ?? uniqid());
            $data['icon']       = $this->gopanelHelper->upload($file, $item->getTable(), 'social-' . $fileName);
        }

        $item  = $this->crudHelper->saveInstance($item, $data);
        return $item;
    }
}
