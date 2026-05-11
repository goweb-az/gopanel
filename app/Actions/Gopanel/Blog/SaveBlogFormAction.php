<?php

namespace App\Actions\Gopanel\Blog;

use App\Actions\Gopanel\Support\SyncModelTranslationsAction;
use App\Helpers\Gopanel\FileUploader;
use App\Models\Site\Blog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveBlogFormAction
{
    use AsAction;

    public function handle(array $form, ?UploadedFile $upload = null, array $translations = []): Blog
    {
        return DB::transaction(function () use ($form, $upload, $translations): Blog {
            $blog = Blog::findOrNew($form['id'] ?? null);

            if ($upload) {
                $form['image'] = FileUploader::toPublic(
                    $upload,
                    (new Blog())->getTable(),
                    FileUploader::nameGenerate(['title' => $translations['az']['title'] ?? 'blog'], 'blog'),
                );
            }

            $blog->fill(collect($form)->except('id')->all());
            $blog->save();

            SyncModelTranslationsAction::run($blog, $translations);

            return $blog;
        });
    }
}
