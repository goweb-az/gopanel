<?php

namespace App\Livewire\Forms;

use App\Helpers\Gopanel\FileUploader;
use App\Models\Site\Slider;

class SliderForm extends BaseForm
{
    public array $form = [
        'id'         => null,
        'link'       => '',
        'image'      => '',
        'is_active'  => true,
        'sort_order' => 0,
    ];

    public mixed $upload = null;

    protected function rules(): array
    {
        return [
            'form.link'       => 'nullable|string|max:255',
            'form.is_active'  => 'boolean',
            'form.sort_order' => 'integer|min:0',
            'upload'          => 'nullable|image|max:4096',
        ];
    }

    public function setItem(Slider $slider): void
    {
        $this->form = [
            'id'         => $slider->id,
            'link'       => $slider->link ?? '',
            'image'      => $slider->image ?? '',
            'is_active'  => (bool) ($slider->is_active ?? true),
            'sort_order' => (int) ($slider->sort_order ?? 0),
        ];

        $this->prepareTranslations($slider);
    }

    public function save(): Slider
    {
        $slider = Slider::findOrNew($this->form['id']);

        if ($this->upload) {
            $fileName = FileUploader::nameGenerate(
                ['title' => $this->translations['az']['title'] ?? 'slider'],
                'slider'
            );
            $this->form['image'] = FileUploader::toPublic(
                $this->upload,
                (new Slider())->getTable(),
                $fileName
            );
        }

        $slider->fill(collect($this->form)->except('id')->all());
        $slider->save();

        $this->form['id'] = $slider->id;
        $this->upload = null;

        $this->syncTranslations($slider);

        return $slider;
    }
}
