<?php

namespace App\Actions\Gopanel\Category;

use App\Models\Navigation\Category;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReorderCategoriesAction
{
    use AsAction;

    /**
     * @param  array<int, int|string>  $ids  Ordered list of Category IDs.
     */
    public function handle(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $id) {
                Category::where('id', $id)->update(['sort_order' => $order]);
            }
        });
    }
}
