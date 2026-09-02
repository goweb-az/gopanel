<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Site\BlogSaveRequest;
use App\Models\Site\Blog;
use App\Services\Gopanel\Content\ContentSaveService;
use Exception;
use Illuminate\Http\Request;

class BlogController extends GoPanelController
{
    public function __construct(private readonly ContentSaveService $content)
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        return view("gopanel.pages.blog.index");
    }




    public function store(Blog $item, Request $request)
    {
        $item = is_null($item->id) ? new Blog() : $item;
        $route = route("gopanel.blog.save", $item);
        return view('gopanel.pages.blog.store', compact("item", "route"));
    }


    public function save(Blog $item, BlogSaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";

            $item = $this->content->save($item, $request->payload(), $request->fileFields());

            $this->response['redirect'] = isset($item->id) ? route("gopanel.blog.index") : false;
            $this->success_response($item, $message);
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }
}
