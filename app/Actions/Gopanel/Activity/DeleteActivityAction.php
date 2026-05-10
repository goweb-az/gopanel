<?php

namespace App\Actions\Gopanel\Activity;

use App\Models\Activity\Activity;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteActivityAction
{
    use AsAction;

    public function handle(int $id): void
    {
        Activity::findOrFail($id)->delete();
    }
}
