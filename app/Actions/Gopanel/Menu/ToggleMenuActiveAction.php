<?php

namespace App\Actions\Gopanel\Menu;

use App\Models\Navigation\Menu;
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleMenuActiveAction
{
    use AsAction;

    public function handle(int $id): Menu
    {
        $menu = Menu::findOrFail($id);
        $menu->is_active = ! $menu->is_active;
        $menu->save();

        return $menu;
    }
}
