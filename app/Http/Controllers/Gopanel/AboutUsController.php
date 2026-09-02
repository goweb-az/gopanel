<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Site\AboutUsSaveRequest;
use App\Models\Site\AboutUs;
use App\Queries\Gopanel\Common\SingleRecordQuery;
use App\Services\Gopanel\Content\ContentSaveService;
use Illuminate\Http\Request;

class AboutUsController extends GoPanelController
{
    public function __construct(private readonly ContentSaveService $content)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $item = (new SingleRecordQuery(AboutUs::class))->currentOrNew();
        $route = route('gopanel.about-us.save', $item);

        return view('gopanel.pages.about_us.index', compact('item', 'route'));
    }

    public function save(AboutUs $item, AboutUsSaveRequest $request)
    {
        try {
            $message = !is_null($item->id) ? 'Məlumat uğurla dəyişdirildi!' : 'Məlumat uğurla yaradıldı!';

            $item = $this->content->save($item, $request->payload(), $request->fileFields());

            $this->success_response($item, $message);
        } catch (\Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }

        return $this->response_json();
    }
}
