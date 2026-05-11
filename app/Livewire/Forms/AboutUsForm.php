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
        ) + $this->metaRules();
    }

    public function setItem(AboutUs $item): void
    {
        $this->form = [
            'id'    => $item->id,
            'image' => $item->image ?? '',
        ];

        $this->prepareTranslations($item);
        $this->prepareMeta($item);
    }

    public function save(): AboutUs
    {
        $item = SaveAboutUsFormAction::run(
            form: $this->form,
            upload: $this->upload,
            translations: $this->translations,
            meta: $this->meta,
            metaUploads: $this->metaUploads,
        );

        $this->form['id'] = $item->id;
        $this->upload     = null;
        foreach ($this->metaUploads as $code => $_) {
            $this->metaUploads[$code] = null;
        }

        return $item;
    }
}
