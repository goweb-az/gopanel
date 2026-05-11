<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\AboutUs\SaveAboutUsFormAction;
use App\Models\Site\AboutUs;

class AboutUsForm extends BaseForm
{
    public array $form = [
        'id'    => null,
        'image' => '',
    ];

    public mixed $upload = null;

    protected function rules(): array
    {
        return [
            'upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ] + $this->translationRules(
            required: ['title' => 255],
            optional: ['description' => 65535],
        );
    }

    public function setItem(AboutUs $item): void
    {
        $this->form = [
            'id'    => $item->id,
            'image' => $item->image ?? '',
        ];

        $this->prepareTranslations($item);
    }

    public function save(): AboutUs
    {
        $item = SaveAboutUsFormAction::run(
            form: $this->form,
            upload: $this->upload,
            translations: $this->translations,
        );

        $this->form['id'] = $item->id;
        $this->upload     = null;

        return $item;
    }
}
