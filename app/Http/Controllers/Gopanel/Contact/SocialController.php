<?php

namespace App\Http\Controllers\Gopanel\Contact;

use App\Enums\Common\SocialIconTypeEnum;
use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Contact\SocialSaveRequest;
use App\Models\Contact\Social;
use App\Queries\Gopanel\Contact\SocialQuery;
use App\Services\Gopanel\Content\ContentSaveService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SocialController extends GoPanelController
{
    public function __construct(
        private readonly ContentSaveService $content,
        private readonly SocialQuery $query,
    ) {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $socials = $this->query->all();

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


    public function save(Social $item, SocialSaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";

            $item = $this->content->save($item, $request->payload(), $request->fileFields());

            $this->response['redirect'] = route("gopanel.contact.socials.index");
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
