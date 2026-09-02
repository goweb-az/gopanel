<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Navigation;

use App\DTOs\Gopanel\FileField;
use App\Http\Requests\Gopanel\GopanelFormRequest;
use App\Rules\TranslatedRequired;

class CategorySaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.categories';

    protected array $translatedFields = ['name', 'description', 'slug'];

    protected array $fileInputs = ['icon_image'];

    public function rules(): array
    {
        return [
            'name'         => ['required', 'array', new TranslatedRequired()],
            'name.*'       => ['nullable', 'string', 'max:255'],
            'description'  => ['nullable', 'array'],
            'slug'         => ['nullable', 'array'],
            'slug.*'       => ['nullable', 'string', 'max:255'],
            // Öz-özünün valideyni olmaq ağacı sonsuz dövrəyə salır
            'parent_id'    => ['nullable', 'integer', 'exists:categories,id', 'different:' . $this->currentId()],
            'icon'         => ['nullable', 'string', 'max:255'],
            'icon_type'    => ['nullable', 'string', 'max:20'],
            'icon_image'   => $this->imageRules(2048),
            'color'        => ['nullable', 'string', 'max:20'],
            'sort_order'   => ['nullable', 'integer'],
            'home_order'   => ['nullable', 'integer'],
            'is_active'    => ['nullable', 'in:0,1'],
            'show_in_home' => ['nullable', 'in:0,1'],
            'show_in_menu' => ['nullable', 'in:0,1'],
        ];
    }

    public function fileFields(): array
    {
        return [
            new FileField(
                input: 'icon_image',
                column: 'icon',
                prefix: 'category-icon',
                typeColumn: 'icon_type',
            ),
        ];
    }

    public function attributes(): array
    {
        return [
            'name'      => 'Ad',
            'parent_id' => 'Valideyn kateqoriya',
        ];
    }

    /** `different:` qaydası üçün cari sətrin id-si (yenidirsə 0). */
    private function currentId(): string
    {
        $item = $this->route('item');

        return (string) (is_object($item) ? ($item->id ?? 0) : 0);
    }
}
