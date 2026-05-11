<?php

namespace App\Actions\Gopanel\Social;

use App\Models\Contact\Social;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteSocialAction
{
    use AsAction;

    public function handle(int $id): void
    {
        Social::findOrFail($id)->delete();
    }
}
