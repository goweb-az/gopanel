<?php

namespace App\Http\Requests\Gopanel\Menus;

use App\Enums\Common\Menu\MenuPositionEnum;
use Illuminate\Foundation\Http\FormRequest;

class MoveMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $positions = MenuPositionEnum::values();

        return [
            'moved_id'         => ['required', 'integer', 'exists:menus,id'],
            'new_parent_id'    => ['nullable', 'integer', 'exists:menus,id'],
            'position'         => ['required', 'string', 'in:' . implode(',', $positions)],
            'siblings'         => ['required', 'array', 'min:1'],
            'siblings.*'       => ['integer', 'distinct', 'exists:menus,id'],
            'source_parent_id' => ['nullable', 'integer', 'exists:menus,id'],
            'source_siblings'  => ['nullable', 'array'],
            'source_siblings.*' => ['integer', 'distinct', 'exists:menus,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'moved_id'      => 'Köçürülən element',
            'new_parent_id' => 'Yeni valideyn',
            'position'      => 'Mövqe',
            'siblings'      => 'Sıra',
        ];
    }

    public function messages(): array
    {
        return [
            'moved_id.required' => 'Köçürülən element mütləqdir.',
            'moved_id.exists'   => 'Köçürülən element mövcud deyil.',
            'position.in'       => 'Seçilmiş mövqe mövcud deyil.',
            'siblings.required' => 'Sıra məlumatı mütləqdir.',
        ];
    }
}
