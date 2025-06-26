<?php

namespace App\Http\Controllers\Gopanel;

use App\Helpers\Gopanel\Site\GoPanelSiteHelper;
use App\Helpers\Gopanel\Site\PageMetaDataHelper;
use App\Http\Controllers\GoPanelController;
use App\Models\Site\Blog;
use Exception;
use Illuminate\Http\Request;

class BlogController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        return view("gopanel.pages.blog.index");
    }




    public function store(Blog $item, Request $request)
    {
        $item   = is_null($item->id) ? new Blog() : $item;
        $route  = route("gopanel.blog.save", $item);
        return view('gopanel.pages.blog.store', compact("item", "route"));
    }


    public function save(Blog $item, Request $request)
    {
        try {
            $data       = $request->except(['_token']);
            $message    = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
            if ($request->hasFile("image")) {
                $file               = $request->file('image');
                $fileName           = (new GoPanelSiteHelper())->file_name_genarte($data);
                $data['image']      = (new GoPanelSiteHelper)->upload($file, $item->getTable(), 'qrgate-blog-' . $fileName);
            }
            $item       = $this->siteService->saveModel($item, $data);
            if (isset($item->id)) {
                $this->siteService->createTranslations($item, $request);
                $metaDataInput = $request->input('meta', []);
                $metaFiles = $request->file('meta', []);
                PageMetaDataHelper::save($item, $metaDataInput, $metaFiles);
            }
            $this->response['redirect'] = isset($item->id) ? route("gopanel.blog.index") : false;
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
