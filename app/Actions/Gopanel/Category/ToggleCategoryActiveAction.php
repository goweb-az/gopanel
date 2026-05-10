<?php

namespace App\Actions\Gopanel\Category;

use App\Models\Navigation\Category;
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleCategoryActiveAction
{
    use AsAction;

    public function handle(int $id): Category
    {
        $category = Category::findOrFail($id);
        $category->is_active = ! $category->is_active;
        $category->save();

        return $category;
    }
}
