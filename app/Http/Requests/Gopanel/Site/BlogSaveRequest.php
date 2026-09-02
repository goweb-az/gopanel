<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Site;

use App\DTOs\Gopanel\FileField;
use App\Http\Requests\Gopanel\GopanelFormRequest;
use App\Rules\TranslatedRequired;

class BlogSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.blog';

    protected array $translatedFields = ['title', 'description', 'slug'];

    protected array $fileInputs = ['image'];

    public function rules(): array
    {
        return [
            'title'       => ['required', 'array', new TranslatedRequired()],
            'title.*'     => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'slug'        => ['nullable', 'array'],
            'slug.*'      => ['nullable', 'string', 'max:255'],
            'date_time'   => ['nullable', 'date'],
            'is_active'   => ['nullable', 'in:0,1'],
            'image'       => $this->imageRules(),
        ];
    }

    public function fileFields(): array
    {
        return [
            new FileField(input: 'image', column: 'image', prefix: 'blog'),
        ];
    }

    public function attributes(): array
    {
        return [
            'title'     => 'Başlıq',
            'date_time' => 'Tarix',
            'image'     => 'Şəkil',
        ];
    }
}
