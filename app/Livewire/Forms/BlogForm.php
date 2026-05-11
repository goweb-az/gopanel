<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\Blog\SaveBlogFormAction;
use App\Models\Site\Blog;

class BlogForm extends BaseForm
{
    public array $form = [
        'id'        => null,
        'image'     => '',
        'date_time' => '',
        'is_active' => true,
        'views'     => 0,
    ];

    public mixed $upload = null;

    protected function rules(): array
    {
        return [
            'form.date_time' => ['nullable', 'date'],
            'form.is_active' => 'boolean',
            'form.views'     => ['integer', 'min:0'],
            'upload'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ] + $this->translationRules(
            required: ['title' => 255],
            optional: ['description' => 65535, 'slug' => 255],
        );
    }

    public function setItem(Blog $blog): void
    {
        $this->form = [
            'id'        => $blog->id,
            'image'     => $blog->image ?? '',
            'date_time' => $blog->date_time ? $blog->date_time->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i'),
            'is_active' => (bool) ($blog->is_active ?? true),
            'views'     => (int) ($blog->views ?? 0),
        ];

        $this->prepareTranslations($blog);
    }

    public function save(): Blog
    {
        $blog = SaveBlogFormAction::run(
            form: $this->form,
            upload: $this->upload,
            translations: $this->translations,
        );

        $this->form['id'] = $blog->id;
        $this->upload     = null;

        return $blog;
    }
}
