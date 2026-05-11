<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\Social\SaveSocialFormAction;
use App\Enums\Common\SocialIconTypeEnum;
use App\Models\Contact\Social;
use Illuminate\Validation\Rule;

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
            'form.name'         => ['required', 'string', 'max:100'],
            'form.url'          => ['required', 'string', 'max:255'],
            'form.icon'         => ['nullable', 'string', 'max:255'],
            'form.icon_type'    => ['required', Rule::enum(SocialIconTypeEnum::class)],
            'form.target_blank' => 'boolean',
            'form.is_active'    => 'boolean',
            'form.sort_order'   => ['integer', 'min:0'],
            'upload'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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
        $social = SaveSocialFormAction::run(
            form: $this->form,
            upload: $this->upload,
        );

        $this->form['id'] = $social->id;
        $this->upload     = null;

        return $social;
    }
}
