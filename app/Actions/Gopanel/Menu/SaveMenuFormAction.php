<?php

namespace App\Actions\Gopanel\Menu;

use App\Actions\Gopanel\Support\SyncModelTranslationsAction;
use App\Models\Navigation\Menu;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveMenuFormAction
{
    use AsAction;

    public function handle(array $form, array $translations = []): Menu
    {
        return DB::transaction(function () use ($form, $translations): Menu {
            $menu = Menu::findOrNew($form['id'] ?? null);
            $menu->fill(collect($form)->except('id')->all());
            $menu->save();

            SyncModelTranslationsAction::run($menu, $translations);

            return $menu;
        });
    }
}
