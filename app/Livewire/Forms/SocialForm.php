<?php

namespace App\Livewire\Forms;

use App\Enums\Common\SocialIconTypeEnum;
use App\Helpers\Gopanel\FileUploader;
use App\Models\Contact\Social;

class SocialForm extends BaseForm
{
    public array $form = [
        'id'           => null,
        'name'         => '',
        'url'          => '',
        'icon'         => '',
        'icon_type'    => 'font',
        'target_blank' => true,
        'is_active'    => true,
        'sort_order'   => 0,
    ];

    public mixed $upload = null;

    protected function rules(): array
    {
        return [
            'form.name'         => 'required|string|max:100',
            'form.url'          => 'required|string|max:255',
            'form.icon'         => 'nullable|string|max:255',
            'form.icon_type'    => 'required|string|in:' . implode(',', SocialIconTypeEnum::values()),
            'form.target_blank' => 'boolean',
            'form.is_active'    => 'boolean',
            'form.sort_order'   => 'integer|min:0',
            'upload'            => 'nullable|image|max:2048',
        ];
    }

    public function setItem(Social $social): void
    {
        $this->form = [
            'id'           => $social->id,
            'name'         => $social->name ?? '',
            'url'          => $social->url ?? '',
            'icon'         => $social->icon ?? '',
            'icon_type'    => $social->icon_type?->value ?? 'font',
            'target_blank' => (bool) ($social->target_blank ?? true),
            'is_active'    => (bool) ($social->is_active ?? true),
            'sort_order'   => (int) ($social->sort_order ?? 0),
        ];
    }

    public function save(): Social
    {
        $social = Social::findOrNew($this->form['id']);

        if ($this->upload && $this->form['icon_type'] === SocialIconTypeEnum::Image->value) {
            $fileName = FileUploader::nameGenerate(['name' => $this->form['name']], 'social');
            $this->form['icon'] = FileUploader::toPublic(
                $this->upload,
                (new Social())->getTable(),
                $fileName
            );
        }

        $social->fill(collect($this->form)->except('id')->all());
        $social->save();

        $this->form['id'] = $social->id;
        $this->upload = null;

        return $social;
    }
}
