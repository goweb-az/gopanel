<?php

namespace App\Actions\Gopanel\Menu;

use App\Models\Navigation\Menu;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReorderMenusAction
{
    use AsAction;

    /**
     * @param  array<int, int|string>  $ids  Ordered list of Menu IDs.
     */
    public function handle(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $id) {
                Menu::where('id', $id)->update(['sort_order' => $order]);
            }
        });
    }
}
