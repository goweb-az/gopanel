<?php

namespace App\Http\Controllers\Gopanel\Settings;

use App\Enums\Common\Menu\MenuTypeEnum;
use App\Enums\Common\Menu\MenuPositionEnum;
use App\Helpers\Gopanel\Site\PageMetaDataHelper;
use App\Helpers\Gopanel\TranslationHelper;
use App\Http\Controllers\GoPanelController;
use App\Models\Navigation\Menu;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class MenuController extends GoPanelController
{

    public ?int $parent_id = null;

    public function __construct()
    {
        parent::__construct();
        $this->parent_id = request()->input("parent_id");
        view()->share(['parent_id' => $this->parent_id]);
    }


    public function index(Request $request)
    {
        $query      = is_null($this->parent_id) ? Menu::whereNull("parent_id") : Menu::where("parent_id", $this->parent_id);
        $menuList   = $query->orderBy("sort_order", "ASC")->get();
        return view("gopanel.pages.settings.menu.index", compact('menuList'));
    }


    public function store(Menu $item, Request $request)
    {
        $item       = is_null($item->id) ? new Menu() : $item;
        $route      = route("gopanel.settings.menu.save", $item);
        $types      = MenuTypeEnum::cases();
        $positions  = MenuPositionEnum::cases();
        $parent_id  = $this->parent_id;
        return view('gopanel.pages.settings.menu.store', compact("item", "route", 'types', 'positions', 'parent_id'));
    }


    public function save(Menu $item, Request $request)
    {
        try {
            $data       = $request->except(['_token']);
            $message    = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
            $item       = $this->crudHelper->saveInstance($item, $data);
            if (isset($item->id)) {
                TranslationHelper::create($item, $request);
                $metaDataInput = $request->input('meta', []);
                $metaFiles = $request->file('meta', []);
                PageMetaDataHelper::save($item, $metaDataInput, $metaFiles);
            }
            $this->response['redirect'] = route("gopanel.settings.menu.index", ['parent_id' => $this->parent_id]);
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
