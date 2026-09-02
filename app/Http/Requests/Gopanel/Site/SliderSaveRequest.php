<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Site;

use App\DTOs\Gopanel\FileField;
use App\Http\Requests\Gopanel\GopanelFormRequest;

class SliderSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.slider';

    protected array $translatedFields = ['title', 'description', 'link_title'];

    protected array $fileInputs = ['image'];

    public function rules(): array
    {
        return [
            'title'       => ['nullable', 'array'],
            'title.*'     => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'link_title'  => ['nullable', 'array'],
            'link'        => ['nullable', 'string', 'max:2048'],
            'sort_order'  => ['nullable', 'integer'],
            'is_active'   => ['nullable', 'in:0,1'],
            // `required` yazılmır: redaktədə fayl seçilmirsə köhnə şəkil qalır,
            // yeni sətirdə isə şəkilsiz slayder yaratmaq mövcud davranışdır.
            'image'       => $this->imageRules(),
        ];
    }

    public function fileFields(): array
    {
        return [
            new FileField(input: 'image', column: 'image', prefix: 'slider'),
        ];
    }

    public function attributes(): array
    {
        return ['image' => 'Şəkil'];
    }
}
