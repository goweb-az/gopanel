<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Site\SliderSaveRequest;
use App\Models\Site\Slider;
use App\Queries\Gopanel\Site\SliderQuery;
use App\Services\Gopanel\Content\ContentSaveService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SliderController extends GoPanelController
{
    public function __construct(
        private readonly ContentSaveService $content,
        private readonly SliderQuery $query,
    ) {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $sliders  = $this->query->ordered();
        $modelKey = Slider::class;

        return view('gopanel.pages.slider.index', compact('sliders', 'modelKey'));
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


    public function save(Slider $item, SliderSaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";

            $item = $this->content->save($item, $request->payload(), $request->fileFields());

            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message']   .= $e->getMessage();
        }
        return $this->response_json();
    }
}
