<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Site;

use App\DTOs\Gopanel\FileField;
use App\Http\Requests\Gopanel\GopanelFormRequest;

class AboutUsSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.about-us';

    protected array $translatedFields = ['title', 'description'];

    protected array $fileInputs = ['image'];

    /** Tək sətirli səhifə - «əlavə» yoxdur, ilk saxlanış da redaktədir. */
    protected function ability(): string
    {
        return $this->module . '.edit';
    }

    public function rules(): array
    {
        return [
            'title'       => ['nullable', 'array'],
            'title.*'     => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'image'       => $this->imageRules(),
        ];
    }

    public function fileFields(): array
    {
        return [
            new FileField(input: 'image', column: 'image', prefix: 'about-us'),
        ];
    }
}
