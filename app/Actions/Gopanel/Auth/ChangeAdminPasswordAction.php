<?php

namespace App\Actions\Gopanel\Auth;

use App\Models\Gopanel\Admin;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;

class ChangeAdminPasswordAction
{
    use AsAction;

    public function handle(Admin $admin, string $newPassword): Admin
    {
        $admin->update(['password' => Hash::make($newPassword)]);

        return $admin->refresh();
    }
}
