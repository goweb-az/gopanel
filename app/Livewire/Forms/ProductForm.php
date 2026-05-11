<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\Product\SaveProductFormAction;
use App\Models\Site\Product;

class ProductForm extends BaseForm
{
    public array $form = [
        'id'        => null,
        'price'     => 0,
        'discount'  => null,
        'image'     => '',
        'is_active' => true,
    ];

    public mixed $upload = null;

    protected function rules(): array
    {
        return [
            'form.price'     => ['required', 'numeric', 'min:0'],
            'form.discount'  => ['nullable', 'numeric', 'min:0'],
            'form.is_active' => 'boolean',
            'upload'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ] + $this->translationRules(
            required: ['title' => 255],
            optional: ['short_description' => 1000, 'description' => 65535, 'slug' => 255],
        );
    }

    public function setItem(Product $product): void
    {
        $this->form = [
            'id'        => $product->id,
            'price'     => (float) ($product->price ?? 0),
            'discount'  => $product->discount !== null ? (float) $product->discount : null,
            'image'     => $product->image ?? '',
            'is_active' => (bool) ($product->is_active ?? true),
        ];

        $this->prepareTranslations($product);
    }

    public function save(): Product
    {
        $product = SaveProductFormAction::run(
            form: $this->form,
            upload: $this->upload,
            translations: $this->translations,
        );

        $this->form['id'] = $product->id;
        $this->upload     = null;

        return $product;
    }
}
