<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Site;

use App\DTOs\Gopanel\FileField;
use App\Http\Requests\Gopanel\GopanelFormRequest;
use App\Rules\TranslatedRequired;

class ProductSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.products';

    protected array $translatedFields = ['title', 'short_description', 'description', 'slug'];

    protected array $fileInputs = ['image'];

    public function rules(): array
    {
        return [
            'title'             => ['required', 'array', new TranslatedRequired()],
            'title.*'           => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'array'],
            'description'       => ['nullable', 'array'],
            'slug'              => ['nullable', 'array'],
            'slug.*'            => ['nullable', 'string', 'max:255'],
            'price'             => ['required', 'numeric', 'min:0'],
            // Endirim qiymətdən böyük ola bilməz - `final_price` mənfi çıxardı
            'discount'          => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'is_active'         => ['nullable', 'in:0,1'],
            'image'             => $this->imageRules(),
        ];
    }

    public function fileFields(): array
    {
        return [
            new FileField(input: 'image', column: 'image', prefix: 'product'),
        ];
    }

    public function attributes(): array
    {
        return [
            'title'    => 'Başlıq',
            'price'    => 'Qiymət',
            'discount' => 'Endirim',
        ];
    }

    public function messages(): array
    {
        return [
            'discount.lte' => 'Endirim məbləği qiymətdən böyük ola bilməz.',
        ];
    }
}
