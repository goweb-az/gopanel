<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public array $response = [];
    public int $response_code = 500;

    public function __construct()
    {
        $this->response = ['status' => 'error', 'message' => '', 'data' => []];
    }


    protected function isPermission($permission)
    {
        return auth('web')->user()->can($permission) or auth('web')->user()->super;
    }

    protected function canAbort($code = 403)
    {
        return abort($code);
    }


    protected function viewShare(array $data)
    {
        return view()->share($data);
    }

    public function uploadImage($model, $upload, $type, $folder)
    {
        @unlink(public_path('/storage/' . $folder . '/' . $model->$type));
        $filename = uniqid() . uniqid() . '.' . $upload->extension();
        $upload->storeAs('public', $folder . '/' . $filename);
        return $filename;
    }

    public function success_response($item = [], $message = ' Məlumat uğurla yaradıldı! ')
    {
        $this->response['status']       = 'success';
        $this->response['message']      = $message;
        $this->response['data']         = $item;
        $this->response_code            = 200;
    }


    public function response_json()
    {
        return response()->json($this->response, $this->response_code);
    }
}
