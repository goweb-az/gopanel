<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\Menu\SaveMenuFormAction;
use App\Enums\Common\Menu\MenuPositionEnum;
use App\Enums\Common\Menu\MenuTypeEnum;
use App\Models\Navigation\Menu;

class MenuForm extends BaseForm
{
    public array $form = [
        'id'            => null,
        'parent_id'     => null,
        'type'          => 'static',
        'position'      => 'header',
        'route_name'    => '',
        'function_name' => '',
        'menuable_type' => null,
        'menuable_id'   => null,
        'sort_order'    => 0,
        'is_active'     => true,
        'is_dropdown'   => false,
    ];

    protected function rules(): array
    {
        return [
            'form.parent_id'     => ['nullable', 'integer', 'exists:menus,id'],
            'form.type'          => ['required', 'string', 'in:' . implode(',', array_column(MenuTypeEnum::cases(), 'value'))],
            'form.position'      => ['required', 'string', 'in:' . implode(',', array_column(MenuPositionEnum::cases(), 'value'))],
            'form.route_name'    => ['nullable', 'string', 'max:255'],
            'form.function_name' => ['nullable', 'string', 'max:255'],
            'form.is_active'     => 'boolean',
            'form.is_dropdown'   => 'boolean',
            'form.sort_order'    => ['integer', 'min:0'],
        ];
    }

    public function setItem(Menu $menu): void
    {
        $this->form = [
            'id'            => $menu->id,
            'parent_id'     => $menu->parent_id,
            'type'          => $menu->type ?? 'static',
            'position'      => $menu->position ?? 'header',
            'route_name'    => $menu->route_name ?? '',
            'function_name' => $menu->function_name ?? '',
            'menuable_type' => $menu->menuable_type,
            'menuable_id'   => $menu->menuable_id,
            'sort_order'    => (int) ($menu->sort_order ?? 0),
            'is_active'     => (bool) ($menu->is_active ?? true),
            'is_dropdown'   => (bool) ($menu->is_dropdown ?? false),
        ];

        $this->prepareTranslations($menu);
    }

    public function save(): Menu
    {
        $menu = SaveMenuFormAction::run(
            form: $this->form,
            translations: $this->translations,
        );

        $this->form['id'] = $menu->id;

        return $menu;
    }
}
