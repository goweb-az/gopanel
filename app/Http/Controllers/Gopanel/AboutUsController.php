<?php

namespace App\Http\Controllers\Gopanel;

use App\Helpers\Gopanel\FileUploader;
use App\Helpers\Gopanel\Site\PageMetaDataHelper;
use App\Helpers\Gopanel\TranslationHelper;
use App\Http\Controllers\GoPanelController;
use App\Models\Site\AboutUs;
use Illuminate\Http\Request;

class AboutUsController extends GoPanelController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $item = AboutUs::latest()->first() ?? new AboutUs();
        $route = route('gopanel.about-us.save', $item);

        return view('gopanel.pages.about_us.index', compact('item', 'route'));
    }

    public function save(AboutUs $item, Request $request)
    {
        try {
            $data = $request->except(['_token', 'meta']);
            $message = !is_null($item->id) ? 'Məlumat uğurla dəyişdirildi!' : 'Məlumat uğurla yaradıldı!';

            if ($request->hasFile('image')) {
                $fileName = FileUploader::nameGenerate($request->all(), 'about-us');
                $data['image'] = FileUploader::toPublic($request->file('image'), $item->getTable(), $fileName);
            }

            $item = $this->crudHelper->saveInstance($item, $data);

            if (isset($item->id)) {
                TranslationHelper::create($item, $request);
                PageMetaDataHelper::save($item, $request->input('meta', []), $request->file('meta', []));
            }

            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }
}
