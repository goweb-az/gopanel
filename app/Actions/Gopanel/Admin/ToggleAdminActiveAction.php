<?php

namespace App\Actions\Gopanel\Admin;

use App\Models\Gopanel\Admin;
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleAdminActiveAction
{
    use AsAction;

    public function handle(int $id): Admin
    {
        $admin = Admin::findOrFail($id);
        $admin->is_active = ! $admin->is_active;
        $admin->save();

        return $admin;
    }
}
