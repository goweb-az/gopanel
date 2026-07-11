<?php

namespace App\Http\Controllers\Gopanel\Settings;

use App\Enums\Common\Menu\MenuTypeEnum;
use App\Enums\Common\Menu\MenuPositionEnum;
use App\Helpers\Gopanel\Site\PageMetaDataHelper;
use App\Helpers\Gopanel\TranslationHelper;
use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Menus\MoveMenuRequest;
use App\Http\Requests\Gopanel\Menus\SortMenuRequest;
use App\Models\Navigation\Menu;
use App\Services\Gopanel\Menus\MenuTreeService;
use Illuminate\Http\Request;

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
        $query = is_null($this->parent_id)
            ? Menu::whereNull("parent_id")
            : Menu::where("parent_id", $this->parent_id);

        $menuList = $query->withCount('childrenAdmin')
            ->orderBy("sort_order", "ASC")
            ->get();

        $parent = $this->parent_id ? Menu::find($this->parent_id) : null;

        return view("gopanel.pages.settings.menu.index", compact('menuList', 'parent'));
    }


    /**
     * Reorder menu items within the current level via a dedicated endpoint
     * (not the generic model/column sortable route).
     */
    public function sort(SortMenuRequest $request, MenuTreeService $tree)
    {
        try {
            $ids = array_column($request->validated('items'), 'id');
            $tree->reorder($ids);
            $this->success_response([], 'Sıralama uğurla yeniləndi');
        } catch (\Throwable $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function move(MoveMenuRequest $request, MenuTreeService $tree)
    {
        try {
            $result = $tree->move($request->validated());
            $this->success_response($result, 'Menyu uğurla köçürüldü');
        } catch (\Throwable $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
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
