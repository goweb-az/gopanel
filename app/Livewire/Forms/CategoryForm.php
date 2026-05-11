<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\Category\SaveCategoryFormAction;
use App\Enums\Common\SocialIconTypeEnum;
use App\Models\Navigation\Category;
use Illuminate\Validation\Rule;

class CategoryForm extends BaseForm
{
    public array $form = [
        'id'           => null,
        'parent_id'    => null,
        'icon'         => '',
        'icon_type'    => 'font',
        'color'        => '',
        'sort_order'   => 0,
        'is_active'    => true,
        'show_in_home' => false,
        'show_in_menu' => false,
        'home_order'   => 0,
    ];

    public mixed $iconUpload = null;

    protected function rules(): array
    {
        return [
            'form.parent_id'    => ['nullable', 'integer', 'exists:categories,id'],
            'form.icon'         => ['nullable', 'string', 'max:255'],
            'form.icon_type'    => ['required', Rule::enum(SocialIconTypeEnum::class)],
            'form.color'        => ['nullable', 'string', 'max:20'],
            'form.sort_order'   => ['integer', 'min:0'],
            'form.home_order'   => ['integer', 'min:0'],
            'form.is_active'    => 'boolean',
            'form.show_in_home' => 'boolean',
            'form.show_in_menu' => 'boolean',
            'iconUpload'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ] + $this->translationRules(
            required: ['name' => 255],
            optional: ['description' => 65535, 'slug' => 255],
        ) + $this->metaRules();
    }

    public function setItem(Category $category): void
    {
        $this->form = [
            'id'           => $category->id,
            'parent_id'    => $category->parent_id,
            'icon'         => $category->icon ?? '',
            'icon_type'    => $category->icon_type?->value ?? $category->icon_type ?? 'font',
            'color'        => $category->color ?? '',
            'sort_order'   => (int) ($category->sort_order ?? 0),
            'is_active'    => (bool) ($category->is_active ?? true),
            'show_in_home' => (bool) ($category->show_in_home ?? false),
            'show_in_menu' => (bool) ($category->show_in_menu ?? false),
            'home_order'   => (int) ($category->home_order ?? 0),
        ];

        $this->prepareTranslations($category);
        $this->prepareMeta($category);
    }

    public function save(): Category
    {
        $category = SaveCategoryFormAction::run(
            form: $this->form,
            iconUpload: $this->iconUpload,
            translations: $this->translations,
            meta: $this->meta,
            metaUploads: $this->metaUploads,
        );

        $this->form['id'] = $category->id;
        $this->iconUpload = null;
        foreach ($this->metaUploads as $code => $_) {
            $this->metaUploads[$code] = null;
        }

        return $category;
    }
}
