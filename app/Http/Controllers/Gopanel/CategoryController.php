<?php

namespace App\Http\Controllers\Gopanel;

use App\Helpers\Gopanel\FileUploader;
use App\Helpers\Gopanel\Site\PageMetaDataHelper;
use App\Helpers\Gopanel\TranslationHelper;
use App\Http\Controllers\GoPanelController;
use App\Models\Navigation\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CategoryController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $categories = Category::with(['children' => function ($q) {
            $q->orderBy('sort_order', 'ASC');
        }])
            ->whereNull('parent_id')
            ->orderBy('sort_order', 'ASC')
            ->get();

        return view("gopanel.pages.categories.index", compact('categories'));
    }

    public function getForm(Category $item, Request $request)
    {
        try {
            $route = route("gopanel.categories.save", $item);
            $parents = Category::whereNull('parent_id')
                ->orderBy('sort_order', 'ASC')
                ->get();

            $this->response['html'] = View::make('gopanel.pages.categories.partials.form', [
                'item'    => $item,
                'route'   => $route,
                'parents' => $parents,
            ])->render();
            $this->success_response([], "Form yaradıldı");
        } catch (Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function save(Category $item, Request $request)
    {
        try {
            $data    = $request->except(['_token', 'icon_file', 'icon_image', 'meta']);
            $message = !is_null($item->id) ? "Kateqoriya uğurla dəyişdirildi!" : "Kateqoriya uğurla yaradıldı!";

            if ($request->hasFile('icon_image')) {
                $fileName = FileUploader::nameGenerate($request->all(), 'category-icon');
                $data['icon'] = FileUploader::toPublic($request->file('icon_image'), $item->getTable(), $fileName);
                $data['icon_type'] = 'image';
            }

            if (($data['icon_type'] ?? null) === 'image' && !$request->hasFile('icon_image')) {
                unset($data['icon']);
            }

            $item = $this->crudHelper->saveInstance($item, $data);

            // Save field_translations (name, description, slug)
            if (isset($item->id)) {
                TranslationHelper::create($item, $request);
                $metaDataInput = $request->input('meta', []);
                $metaFiles = $request->file('meta', []);
                PageMetaDataHelper::save($item, $metaDataInput, $metaFiles);
            }

            $this->response['redirect'] = route('gopanel.categories.index');
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function moveCategory(Request $request)
    {
        try {
            $item = Category::findOrFail($request->id);
            $item->parent_id = $request->parent_id;
            $item->save();

            $this->success_response($item, "Kateqoriya uğurla köçürüldü!");
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }
}
