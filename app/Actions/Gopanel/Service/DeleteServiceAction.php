<?php

namespace App\Actions\Gopanel\Service;

use App\Models\Site\Service;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteServiceAction
{
    use AsAction;

    public function handle(int $id): void
    {
        Service::findOrFail($id)->delete();
    }
}
