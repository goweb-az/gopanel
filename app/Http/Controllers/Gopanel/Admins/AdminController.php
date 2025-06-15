<?php

namespace App\Http\Controllers\Gopanel\Admins;

use App\Http\Controllers\GoPanelController;
use App\Models\Gopanel\Admin;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AdminController extends GoPanelController
{

    public function __construct()
    {
        // code 
    }


    public function index(Request $request)
    {
        return view("gopanel.pages.admins.index");
    }

    public function getForm(Admin $item, Request $request)
    {
        try {
            $route = route("gopanel.admins.save", $item);
            $this->response['html'] = View::make('gopanel.pages.admins.partials.form', [
                'item'          => $item,
                'route'         => $route,
            ])->render();
            $this->success_response([], "Form yaradıldı");
        } catch (Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
