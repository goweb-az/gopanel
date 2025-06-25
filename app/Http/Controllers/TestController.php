<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(Request $request)
    {
        $this->tesguadr();
    }



    private function tesguadr()
    {
        foreach (config('auth.guards') as $key => $guard) {
            dd($guard);
        }
    }

    private function permission()
    {
        $permission_list = config('gopanel.permission_list');

        foreach ($permission_list as $group => $permissions) {
            foreach ($permissions as $permission) {
                $exists = CustomPermission::where('name', $permission['name'])
                    ->where('group', $group)
                    ->exists();
                if (!$exists) {
                    CustomPermission::updateOrCreate($permission);
                }
            }
        }
    }
}
