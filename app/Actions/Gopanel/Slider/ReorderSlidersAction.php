<?php

namespace App\Actions\Gopanel\Slider;

use App\Models\Site\Slider;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReorderSlidersAction
{
    use AsAction;

    /**
     * @param  array<int, int|string>  $ids  Ordered list of Slider IDs.
     */
    public function handle(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $id) {
                Slider::where('id', $id)->update(['sort_order' => $order]);
            }
        });
    }
}
