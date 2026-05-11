<?php

namespace App\Actions\Gopanel\Blog;

use App\Models\Site\Blog;
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleBlogActiveAction
{
    use AsAction;

    public function handle(int $id): Blog
    {
        $blog = Blog::findOrFail($id);
        $blog->is_active = ! $blog->is_active;
        $blog->save();

        return $blog;
    }
}
