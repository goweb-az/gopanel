<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Contact;

use App\DTOs\Gopanel\FileField;
use App\Enums\Common\SocialIconTypeEnum;
use App\Http\Requests\Gopanel\GopanelFormRequest;
use Illuminate\Validation\Rule;

class SocialSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.contact.socials';

    protected array $fileInputs = ['image'];

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'url'          => ['required', 'string', 'max:2048'],
            'icon'         => ['nullable', 'string'],
            'icon_type'    => ['nullable', Rule::in(SocialIconTypeEnum::values())],
            'image'        => $this->imageRules(2048),
            'target_blank' => ['nullable', 'in:0,1'],
            'is_active'    => ['nullable', 'in:0,1'],
            'sort_order'   => ['nullable', 'integer'],
        ];
    }

    /**
     * Şəkil `image` input-undan gəlir, amma `icon` sütununa yazılır -
     * modeldə sosial şəbəkənin ikonu (font, svg və ya şəkil) tək sütundadır.
     */
    public function fileFields(): array
    {
        return [
            new FileField(input: 'image', column: 'icon', prefix: 'social'),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Ad',
            'url'  => 'Link',
        ];
    }
}
