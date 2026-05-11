<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\Service\SaveServiceFormAction;
use App\Enums\Common\SocialIconTypeEnum;
use App\Models\Site\Service;
use Illuminate\Validation\Rule;

class ServiceForm extends BaseForm
{
    public array $form = [
        'id' => null,
        'icon' => '',
        'icon_type' => 'font',
        'image' => '',
        'sort_order' => 0,
    ];

    public mixed $iconUpload = null;

    public mixed $imageUpload = null;

    protected function rules(): array
    {
        return [
            'form.icon' => ['nullable', 'string', 'max:255'],
            'form.icon_type' => ['required', Rule::enum(SocialIconTypeEnum::class)],
            'form.sort_order' => ['integer', 'min:0'],
            'iconUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'imageUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ] + $this->translationRules(
            required: ['title' => 255],
            optional: ['short_description' => 1000, 'description' => 65535],
        ) + $this->metaRules();
    }

    public function setItem(Service $service): void
    {
        $this->form = [
            'id' => $service->id,
            'icon' => $service->icon ?? '',
            'icon_type' => $service->icon_type?->value ?? 'font',
            'image' => $service->image ?? '',
            'sort_order' => (int) ($service->sort_order ?? 0),
        ];

        $this->prepareTranslations($service);
        $this->prepareMeta($service);
    }

    public function save(): Service
    {
        $service = SaveServiceFormAction::run(
            form: $this->form,
            iconUpload: $this->iconUpload,
            imageUpload: $this->imageUpload,
            translations: $this->translations,
            meta: $this->meta,
            metaUploads: $this->metaUploads,
        );

        $this->form['id'] = $service->id;
        $this->iconUpload = null;
        $this->imageUpload = null;
        foreach ($this->metaUploads as $code => $_) {
            $this->metaUploads[$code] = null;
        }

        return $service;
    }
}
