<?php

namespace App\Http\Controllers\Gopanel;

use App\Helpers\Gopanel\FileUploader;
use App\Helpers\Gopanel\Site\PageMetaDataHelper;
use App\Helpers\Gopanel\TranslationHelper;
use App\Http\Controllers\GoPanelController;
use App\Models\Site\Product;
use Illuminate\Http\Request;

class ProductController extends GoPanelController
{
    public function __construct()
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

    public function save(Product $item, Request $request)
    {
        try {
            $data    = $request->except(['_token', 'meta']);
            $message = !is_null($item->id) ? 'Məhsul uğurla dəyişdirildi!' : 'Məhsul uğurla yaradıldı!';

            if ($request->hasFile('image')) {
                $fileName = FileUploader::nameGenerate($request->all(), 'product');
                $data['image'] = FileUploader::toPublic($request->file('image'), $item->getTable(), $fileName);
            }

            $item = $this->crudHelper->saveInstance($item, $data);

            if (isset($item->id)) {
                TranslationHelper::create($item, $request);
                PageMetaDataHelper::save($item, $request->input('meta', []), $request->file('meta', []));
            }

            $this->response['redirect'] = isset($item->id) ? route('gopanel.products.index') : false;
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }
}
