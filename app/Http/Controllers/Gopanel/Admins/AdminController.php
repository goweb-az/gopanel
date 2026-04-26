<?php

namespace App\Http\Controllers\Gopanel\Admins;

use App\Helpers\Gopanel\FileUploader;
use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Admin\AdminStoreRequest;
use App\Models\Gopanel\Admin;
use App\Models\Gopanel\CustomRole;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class AdminController extends GoPanelController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        return view('gopanel.pages.admins.index');
    }

    public function getForm(Admin $item, Request $request)
    {
        try {
            $route = route('gopanel.admins.save', $item);
            $this->response['html'] = View::make('gopanel.pages.admins.partials.form', [
                'item' => $item,
                'route' => $route,
                'roles' => CustomRole::all(),
            ])->render();
            $this->success_response([], 'Form yaradıldı');
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    public function save(Admin $item, AdminStoreRequest $request)
    {
        try {
            if ($request->_change_password_only && !is_null($item->id)) {
                if (empty($request->password)) {
                    throw new \Exception('Şifrə boş ola bilməz.');
                }

                if ($request->password !== $request->password_confirmation) {
                    throw new \Exception('Şifrə təsdiqi uyğun gəlmir.');
                }

                $item->update(['password' => Hash::make($request->password)]);
                $this->success_response($item, 'Şifrə uğurla dəyişdirildi!');

                return $this->response_json();
            }

            $isCreate = is_null($item->id);
            $message = !$isCreate ? 'Məlumat uğurla dəyişdirildi!' : 'Məlumat uğurla yaradıldı!';
            $data = $this->renderStoredata($request);
            $item = $this->crudHelper->saveInstance($item, $data);

            if ($isCreate && empty($item->image)) {
                $item->image = $this->generateAvatarFromApi($item);
                $item->save();
            }

            if (isset($item->id) && !empty($request->role)) {
                $role = CustomRole::find($request->role);
                $item->syncRoles($role->name);
            }

            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    private function generateAvatarFromApi(Admin $item): ?string
    {
        try {
            $name = urlencode($item->full_name ?? 'Admin');
            $url = "https://ui-avatars.com/api/?name={$name}&background=556ee6&color=fff&size=256&font-size=0.4&format=png";
            $contents = file_get_contents($url);

            if ($contents) {
                $folder = 'admins';
                $filename = 'admin-' . $item->id . '.png';
                Storage::disk('public')->put($folder . '/' . $filename, $contents);

                return "{$folder}/{$filename}";
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    public function renderStoredata($request)
    {
        $data = $request->except(['_token', 'image']);

        if (!empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $data['image'] = FileUploader::toStorage($file, 'admins', 'admin-' . time());
        }

        return $data;
    }
}
