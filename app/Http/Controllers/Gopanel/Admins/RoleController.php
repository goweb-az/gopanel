<?php

namespace App\Http\Controllers\Gopanel\Admins;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Admin\RoleStoreRequest;
use App\Models\Gopanel\Admin;
use App\Models\Gopanel\CustomPermission;
use App\Models\Gopanel\CustomRole;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class RoleController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        return view("gopanel.pages.roles.index");
    }

    public function store(CustomRole $item, Request $request)
    {
        $item           = is_null($item->id) ? new CustomRole() : $item;
        $route          = route("gopanel.admins.roles.save", $item);
        $permissions    = CustomPermission::all()->groupBy('group');
        return view('gopanel.pages.roles.store', compact("item", "route", 'permissions'));
    }


    public function save(CustomRole $item, RoleStoreRequest $request)
    {
        try {
            $permissions    = $request->permissions;
            $data           = $request->except(['_token', 'permissions']);
            $message        = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
            $item           = $this->siteService->saveModel($item, $data);
            if (count($permissions ?? []))
                $item->syncPermissions($permissions);
            $this->response['redirect'] = isset($item->id) ? route("gopanel.admins.roles.index") : false;
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
