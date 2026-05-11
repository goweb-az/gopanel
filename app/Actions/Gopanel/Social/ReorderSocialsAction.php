<?php

namespace App\Actions\Gopanel\Social;

use App\Models\Contact\Social;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReorderSocialsAction
{
    use AsAction;

    /**
     * @param  array<int, int|string>  $ids  Ordered list of Social IDs.
     */
    public function handle(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            foreach ($ids as $order => $id) {
                Social::where('id', $id)->update(['sort_order' => $order]);
            }
        });
    }
}
