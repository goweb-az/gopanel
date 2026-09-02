<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Site\ServiceSaveRequest;
use App\Models\Site\Service;
use App\Queries\Gopanel\Site\ServiceQuery;
use App\Services\Gopanel\Content\ContentSaveService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ServiceController extends GoPanelController
{
    public function __construct(
        private readonly ContentSaveService $content,
        private readonly ServiceQuery $query,
    ) {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $services  = $this->query->ordered();
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

    public function save(Service $item, ServiceSaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? 'Məlumat uğurla dəyişdirildi!' : 'Məlumat uğurla yaradıldı!';

            $item = $this->content->save($item, $request->payload(), $request->fileFields());

            $this->response['redirect'] = route('gopanel.services.index');
            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }
}
