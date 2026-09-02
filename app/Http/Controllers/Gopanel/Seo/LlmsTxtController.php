<?php

namespace App\Http\Controllers\Gopanel\Seo;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Seo\LlmsTxtSaveRequest;
use App\Models\Seo\LlmsTxt;
use App\Queries\Gopanel\Common\SingleRecordQuery;
use App\Services\Gopanel\Seo\LlmsTxtService;
use Illuminate\Http\Request;

class LlmsTxtController extends GoPanelController
{
    public function __construct(private readonly LlmsTxtService $service)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $item = (new SingleRecordQuery(LlmsTxt::class))->currentOrNew();

        return view("gopanel.pages.seo.llms-txt.index", compact("item"));
    }

    public function save(LlmsTxt $item, LlmsTxtSaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";

            $item = $this->service->save($item, $request->payload());

            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }
}
