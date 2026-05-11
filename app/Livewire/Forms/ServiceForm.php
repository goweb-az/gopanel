<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\Service\SaveServiceFormAction;
use App\Enums\Common\SocialIconTypeEnum;
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
        $service = SaveServiceFormAction::run(
            form: $this->form,
            iconUpload: $this->iconUpload,
            imageUpload: $this->imageUpload,
            translations: $this->translations,
        );

        $this->form['id']  = $service->id;
        $this->iconUpload  = null;
        $this->imageUpload = null;

        return $service;
    }
}
