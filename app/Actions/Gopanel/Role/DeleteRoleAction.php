<?php

namespace App\Actions\Gopanel\Role;

use App\Models\Gopanel\CustomRole;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteRoleAction
{
    use AsAction;

    public function handle(int $id): void
    {
        CustomRole::findOrFail($id)->delete();
    }
}
