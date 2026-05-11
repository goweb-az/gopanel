<?php

namespace App\Actions\Gopanel\Social;

use App\Models\Contact\Social;
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleSocialActiveAction
{
    use AsAction;

    public function handle(int $id): Social
    {
        $social = Social::findOrFail($id);
        $social->is_active = ! $social->is_active;
        $social->save();

        return $social;
    }
}
