<?php

namespace App\Actions\Gopanel\Service;

use App\Models\Site\Service;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReorderServicesAction
{
    use AsAction;

    /**
     * @param  array<int, int|string>  $ids  Ordered list of Service IDs.
     */
    public function handle(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            foreach ($ids as $order => $id) {
                Service::where('id', $id)->update(['sort_order' => $order]);
            }
        });
    }
}
