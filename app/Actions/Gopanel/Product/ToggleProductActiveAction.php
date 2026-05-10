<?php

namespace App\Actions\Gopanel\Product;

use App\Models\Site\Product;
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleProductActiveAction
{
    use AsAction;

    public function handle(int $id): Product
    {
        $product = Product::findOrFail($id);
        $product->is_active = ! $product->is_active;
        $product->save();

        return $product;
    }
}
