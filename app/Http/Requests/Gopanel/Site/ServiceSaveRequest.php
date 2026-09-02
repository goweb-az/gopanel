<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Site;

use App\DTOs\Gopanel\FileField;
use App\Http\Requests\Gopanel\GopanelFormRequest;
use App\Rules\TranslatedRequired;

class ServiceSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.services';

    protected array $translatedFields = ['title', 'short_description', 'description'];

    protected array $fileInputs = ['icon_image', 'image'];

    public function rules(): array
    {
        return [
            'title'             => ['required', 'array', new TranslatedRequired()],
            'title.*'           => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'array'],
            'description'       => ['nullable', 'array'],
            'icon'              => ['nullable', 'string', 'max:255'],
            'icon_type'         => ['nullable', 'string', 'max:20'],
            'icon_image'        => $this->imageRules(2048),
            'image'             => $this->imageRules(),
            'sort_order'        => ['nullable', 'integer'],
        ];
    }

    public function fileFields(): array
    {
        return [
            new FileField(
                input: 'icon_image',
                column: 'icon',
                prefix: 'service-icon',
                typeColumn: 'icon_type',
            ),
            new FileField(input: 'image', column: 'image', prefix: 'service'),
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'Başlıq'];
    }
}
