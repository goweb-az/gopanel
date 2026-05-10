<?php

namespace App\Livewire\Forms;

use App\Enums\Common\SocialIconTypeEnum;
use App\Helpers\Gopanel\FileUploader;
use App\Models\Site\Service;

class ServiceForm extends BaseForm
{
    public array $form = [
        'id'         => null,
        'icon'       => '',
        'icon_type'  => 'font',
        'image'      => '',
        'sort_order' => 0,
    ];

    public mixed $iconUpload = null;

    public mixed $imageUpload = null;

    protected function rules(): array
    {
        return [
            'form.icon'       => 'nullable|string|max:255',
            'form.icon_type'  => 'required|string|in:' . implode(',', SocialIconTypeEnum::values()),
            'form.sort_order' => 'integer|min:0',
            'iconUpload'      => 'nullable|image|max:2048',
            'imageUpload'     => 'nullable|image|max:4096',
        ];
    }

    public function setItem(Service $service): void
    {
        $this->form = [
            'id'         => $service->id,
            'icon'       => $service->icon ?? '',
            'icon_type'  => $service->icon_type?->value ?? 'font',
            'image'      => $service->image ?? '',
            'sort_order' => (int) ($service->sort_order ?? 0),
        ];

        $this->prepareTranslations($service);
    }

    public function save(): Service
    {
        $service = $this->form['id']
            ? Service::findOrFail($this->form['id'])
            : new Service();

        if ($this->iconUpload && $this->form['icon_type'] === SocialIconTypeEnum::Image->value) {
            $fileName = FileUploader::nameGenerate(
                ['title' => $this->translations['az']['title'] ?? 'service'],
                'service-icon'
            );
            $this->form['icon'] = FileUploader::toPublic(
                $this->iconUpload,
                (new Service())->getTable(),
                $fileName
            );
        }

        if ($this->imageUpload) {
            $fileName = FileUploader::nameGenerate(
                ['title' => $this->translations['az']['title'] ?? 'service'],
                'service'
            );
            $this->form['image'] = FileUploader::toPublic(
                $this->imageUpload,
                (new Service())->getTable(),
                $fileName
            );
        }

        $service->fill(collect($this->form)->except('id')->all());
        $service->save();

        $this->form['id'] = $service->id;
        $this->iconUpload = null;
        $this->imageUpload = null;

        $this->syncTranslations($service);

        return $service;
    }
}
