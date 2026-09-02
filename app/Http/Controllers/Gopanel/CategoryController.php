<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Navigation\CategorySaveRequest;
use App\Models\Navigation\Category;
use App\Queries\Gopanel\Navigation\CategoryQuery;
use App\Services\Gopanel\Content\ContentSaveService;
use App\Services\Gopanel\Navigation\CategoryTreeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CategoryController extends GoPanelController
{
    public function __construct(
        private readonly ContentSaveService $content,
        private readonly CategoryQuery $query,
        private readonly CategoryTreeService $tree,
    ) {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $categories = $this->query->tree();

        return view("gopanel.pages.categories.index", compact('categories'));
    }

    public function getForm(Category $item, Request $request)
    {
        try {
            $route = route("gopanel.categories.save", $item);

            $this->response['html'] = View::make('gopanel.pages.categories.partials.form', [
                'item'    => $item,
                'route'   => $route,
                'parents' => $this->query->roots(),
            ])->render();
            $this->success_response([], "Form yaradıldı");
        } catch (Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function save(Category $item, CategorySaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? "Kateqoriya uğurla dəyişdirildi!" : "Kateqoriya uğurla yaradıldı!";

            $item = $this->content->save($item, $request->payload(), $request->fileFields());

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
            $item = $this->tree->move(
                (int) $request->input('id'),
                $request->input('parent_id') === null ? null : (int) $request->input('parent_id'),
            );

            $this->success_response($item, "Kateqoriya uğurla köçürüldü!");
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }
}
