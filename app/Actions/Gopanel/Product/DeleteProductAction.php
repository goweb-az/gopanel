<?php

namespace App\Actions\Gopanel\Product;

use App\Models\Site\Product;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteProductAction
{
    use AsAction;

    public function handle(int $id): void
    {
        Product::findOrFail($id)->delete();
    }
}
