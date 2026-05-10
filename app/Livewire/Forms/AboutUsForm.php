<?php

namespace App\Livewire\Forms;

use App\Helpers\Gopanel\FileUploader;
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
            'upload' => 'nullable|image|max:4096',
        ];
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
        $item = AboutUs::findOrNew($this->form['id']);

        if ($this->upload) {
            $fileName = FileUploader::nameGenerate(
                ['title' => $this->translations['az']['title'] ?? 'about-us'],
                'about-us'
            );
            $this->form['image'] = FileUploader::toPublic(
                $this->upload,
                (new AboutUs())->getTable(),
                $fileName
            );
        }

        $item->fill(collect($this->form)->except('id')->all());
        $item->save();

        $this->form['id'] = $item->id;
        $this->upload = null;

        $this->syncTranslations($item);

        return $item;
    }
}
