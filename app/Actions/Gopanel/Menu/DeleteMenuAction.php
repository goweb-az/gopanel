<?php

namespace App\Actions\Gopanel\Menu;

use App\Models\Navigation\Menu;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteMenuAction
{
    use AsAction;

    public function handle(int $id): void
    {
        Menu::findOrFail($id)->delete();
    }
}
