<?php

namespace App\Actions\Gopanel\Admin;

use App\Models\Gopanel\Admin;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAdminAction
{
    use AsAction;

    public function handle(int $id): void
    {
        Admin::findOrFail($id)->delete();
    }
}
