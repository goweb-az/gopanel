<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Site\ProductSaveRequest;
use App\Models\Site\Product;
use App\Services\Gopanel\Content\ContentSaveService;
use Illuminate\Http\Request;

class ProductController extends GoPanelController
{
    public function __construct(private readonly ContentSaveService $content)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        return view('gopanel.pages.products.index');
    }

    public function store(Product $item, Request $request)
    {
        $item = is_null($item->id) ? new Product() : $item;
        $route = route('gopanel.products.save', $item);

        return view('gopanel.pages.products.store', compact('item', 'route'));
    }

    public function save(Product $item, ProductSaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? 'Məhsul uğurla dəyişdirildi!' : 'Məhsul uğurla yaradıldı!';

            $item = $this->content->save($item, $request->payload(), $request->fileFields());

            $this->response['redirect'] = isset($item->id) ? route('gopanel.products.index') : false;
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }
}
