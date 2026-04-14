<?php

namespace Database\Seeders;

use App\Helpers\Gopanel\TranslationHelper;
use App\Models\Navigation\Menu;
use App\Models\Service\Service;
use App\Models\Translations\Translation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        foreach ($this->data() as $key => $value) {
            $parentMenu = $this->create($value);
            if (!is_null($parentMenu?->id) && isset($value['children'])) {
                if (is_array($value['children']) && count($value['children'])) {
                    foreach ($value['children'] as $key => $children) {
                        $childe = $this->create($children, $parentMenu->id);
                    }
                }
            }
        }
    }

    private function create($data, $parent_id = null)
    {
        $menu = $this->check($data);
        if (is_null($menu?->id)) {
            $menu                   = new Menu();
            $menu->parent_id        = $parent_id ?? NULL;
            $menu->route_name       = $data['route_name'] ?? NULL;
            $menu->type             = $data['type'] ?? NULL;
            $menu->function_name    = $data['function_name'] ?? NULL;
            $menu->position         = $data['position'] ?? NULL;
            $menu->is_active        = $data['is_active'] ?? false;
            $menu->is_dropdown      = $data['is_dropdown'] ?? false;
            $menu->sort_order       = $data['sort_order'] ?? 0;
            $menu->save();
            $menu = $menu->fresh();
            if (!is_null($menu->id)) {
                $title              = $data['title'] ?? [];
                $slug               = $data['slug'] ?? [];
                $description        = $data['description'] ?? [];
                if (count($title)) {
                    TranslationHelper::basic($menu, $title, "title");
                }
                if (count($slug)) {
                    TranslationHelper::basic($menu, $slug, "slug");
                }
                if (count($description)) {
                    TranslationHelper::basic($menu, $description, "description");
                }
            }
        }
        return $menu;
    }


    private function check($data)
    {
        return Menu::where("route_name", $data['route_name'])
            ->where("type", $data['type'])
            ->where("position", $data['position'])
            ->first();
    }


    private function data()
    {
        return [
            [
                "route_name"    => "home",
                "type"          => "route",
                "position"      => "other",
                "is_active"     => 1,
                "is_dropdown"   => 0,
                'sort_order'    => 0,
                'title'         => [
                    'az' => 'Ana Səhifə',
                    'en' => 'Home',
                    'ru' => 'Главная',
                ],
            ],
            [
                "route_name"    => "blogs",
                "type"          => "route",
                "position"      => "footer_community",
                "is_active"     => 1,
                "is_dropdown"   => 0,
                'sort_order'    => 1,
                'title'         => [
                    'az' => 'Bloqlar',
                    'en' => 'Blogs',
                    'ru' => 'Блоги',
                ],
                'slug'         => [
                    'az' => 'bloqlar',
                    'en' => 'blogs',
                    'ru' => 'blogi',
                ],
            ],
            [
                "route_name"    => "contact",
                "type"          => "route",
                "position"      => "header",
                "is_active"     => 1,
                "is_dropdown"   => 0,
                'sort_order'    => 2,
                'title'         => [
                    'az' => 'Əlaqə',
                    'en' => 'Contact',
                    'ru' => 'Контакт',
                ],
                'slug'         => [
                    'az' => 'elaqe',
                    'en' => 'contact',
                    'ru' => 'kontakt',
                ],
            ],

        ];
    }
}
