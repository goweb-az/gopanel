<?php

namespace App\Http\Controllers\Gopanel\Admins;

use App\Helpers\Gopanel\FileUploader;
use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Admin\ChangePasswordRequest;
use App\Http\Requests\Gopanel\Admin\ProfileUpdateRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $item = Auth::guard('gopanel')->user();
        return view('gopanel.pages.profile.index', compact('item'));
    }

    public function update(ProfileUpdateRequest $request)
    {
        try {
            $item = Auth::guard('gopanel')->user();
            $data = $request->only(['full_name', 'email']);

            if ($request->hasFile('image')) {
                $file     = $request->file('image');
                $data['image'] = FileUploader::toStorage($file, 'admins', 'admin-' . $item->id . '-' . time());
            }

            $item->update($data);
            // Avatar cache-i təmizlə
            Cache::forget("admin_avatar_{$item->id}");
            $this->success_response($item, 'Profil məlumatları uğurla yeniləndi!');
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }

    public function changePasswordIndex()
    {
        return view('gopanel.pages.profile.change-password');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $item = Auth::guard('gopanel')->user();
            $item->update(['password' => Hash::make($request->password)]);
            $this->response['redirect'] = route('gopanel.profile.index');
            $this->success_response($item, 'Şifrə uğurla dəyişdirildi!');
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }
}
