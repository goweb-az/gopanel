<?php

namespace App\Actions\Gopanel\Blog;

use App\Models\Site\Blog;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteBlogAction
{
    use AsAction;

    public function handle(int $id): void
    {
        Blog::findOrFail($id)->delete();
    }
}
