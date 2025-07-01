<?php

namespace App\Http\Controllers\Gopanel\Admins;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Admin\AdminStoreRequest;
use App\Models\Gopanel\Admin;
use App\Models\Gopanel\CustomRole;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;

class AdminController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
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
                'roles'         => CustomRole::all()
            ])->render();
            $this->success_response([], "Form yaradıldı");
        } catch (Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function save(Admin $item, AdminStoreRequest $request)
    {
        try {
            $message    = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
            $data       = $this->renderStoredata($request);
            $item       = $this->crudHelper->saveInstance($item, $data);
            if (isset($item->id) && !empty($request->role)) {
                $role = CustomRole::find($request->role);
                $item->syncRoles($role->name);
            }
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function renderStoredata($request)
    {
        $data       = $request->except(['_token']);
        if (!empty($request->password))
            $data['password'] = Hash::make($request->password);
        else
            unset($data['password']);
        return $data;
    }
}
