<?php

namespace App\Actions\Gopanel\Slider;

use App\Models\Site\Slider;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteSliderAction
{
    use AsAction;

    public function handle(int $id): void
    {
        Slider::findOrFail($id)->delete();
    }
}
