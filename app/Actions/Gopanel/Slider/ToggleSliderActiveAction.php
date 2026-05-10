<?php

namespace App\Actions\Gopanel\Slider;

use App\Models\Site\Slider;
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleSliderActiveAction
{
    use AsAction;

    public function handle(int $id): Slider
    {
        $slider = Slider::findOrFail($id);
        $slider->is_active = ! $slider->is_active;
        $slider->save();

        return $slider;
    }
}
