<?php

namespace App\Http\Controllers\Gopanel;

use App\Helpers\Gopanel\FileUploader;
use App\Helpers\Gopanel\Site\PageMetaDataHelper;
use App\Helpers\Gopanel\TranslationHelper;
use App\Http\Controllers\GoPanelController;
use App\Models\Site\Service;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ServiceController extends GoPanelController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $services  = Service::orderBy('sort_order')->get();
        $modelKey  = Service::class;

        return view('gopanel.pages.services.index', compact('services', 'modelKey'));
    }

    public function getForm(Service $item, Request $request)
    {
        try {
            $route = route('gopanel.services.save', $item);

            $this->response['html'] = View::make('gopanel.pages.services.partials.form', [
                'item' => $item,
                'route' => $route,
            ])->render();
            $this->success_response([], 'Form yaradıldı');
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }

    public function save(Service $item, Request $request)
    {
        try {
            $data = $request->except(['_token', 'meta', 'icon_image']);
            $message = !is_null($item->id) ? 'Məlumat uğurla dəyişdirildi!' : 'Məlumat uğurla yaradıldı!';

            if ($request->hasFile('icon_image')) {
                $fileName = FileUploader::nameGenerate($request->all(), 'service-icon');
                $data['icon'] = FileUploader::toPublic($request->file('icon_image'), $item->getTable(), $fileName);
                $data['icon_type'] = 'image';
            }

            if (($data['icon_type'] ?? null) === 'image' && !$request->hasFile('icon_image')) {
                unset($data['icon']);
            }

            if ($request->hasFile('image')) {
                $fileName = FileUploader::nameGenerate($request->all(), 'service');
                $data['image'] = FileUploader::toPublic($request->file('image'), $item->getTable(), $fileName);
            }

            $item = $this->crudHelper->saveInstance($item, $data);

            if (isset($item->id)) {
                TranslationHelper::create($item, $request);
                PageMetaDataHelper::save($item, $request->input('meta', []), $request->file('meta', []));
            }

            $this->response['redirect'] = route('gopanel.services.index');
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }
}
