<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\GoPanelController;
use App\Models\Site\Slider;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SliderController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        return view("gopanel.pages.slider.index");
    }

    public function getForm(Slider $item, Request $request)
    {
        try {
            $route = route("gopanel.slider.save", $item);
            $this->response['html'] = View::make('gopanel.pages.slider.partials.form', [
                'item'          => $item,
                'route'         => $route,
            ])->render();
            $this->success_response([], "Form yaradıldı");
        } catch (Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }


    public function save(Slider $item, Request $request)
    {
        try {
            $data       = $request->except(['_token']);
            $message    = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
            if ($request->hasFile("image")) {
                $file               = $request->file('image');
                $fileName           = $this->gopanelHelper->file_name_genarte($data);
                $data['image']      = $this->gopanelHelper->upload($file, $item->getTable(), 'slider-' . $fileName);
            }
            $item       = $this->siteService->saveModel($item, $data);
            if (isset($item->id)) {
                $this->siteService->createTranslations($item, $request);
            }
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
