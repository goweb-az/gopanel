<?php

namespace App\Actions\Gopanel\Category;

use App\Models\Navigation\Category;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteCategoryAction
{
    use AsAction;

    public function handle(int $id): void
    {
        Category::findOrFail($id)->delete();
    }
}
