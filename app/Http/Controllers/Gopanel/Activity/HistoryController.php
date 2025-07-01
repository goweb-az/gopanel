<?php

namespace App\Http\Controllers\Gopanel\Activity;

use App\Http\Controllers\GoPanelController;
use App\Models\Activity\Activity;
use Exception;
use Illuminate\Http\Request;

class HistoryController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    // public function index(Request $request)
    // {
    //     return view("gopanel.pages.histories.index");
    // }

    public function index(Request $request)
    {
        $from               = $request->from;
        $to                 = $request->to;
        $event              = $request->event;
        $events             = config("activitylog.event_names");
        return view("gopanel.pages.activity.histories.index", compact(
            'from',
            'to',
            'events',
            'event',
        ));
    }

    public function show(Activity $history, Request $request)
    {

        try {
            $view       = view('gopanel.pages.activity.histories.inc.show', compact('history'))->render();
            $this->response['html']             = $view;
            $this->response['message']          = "Success view";
            $this->response['status']           = "success";
            $this->response['data']             = $history?->toArray();
            $this->response['data_id']          = '#' . $history->id;
            $this->response_code                = 200;
        } catch (\Exception $e) {
            $this->response['message'] = $e->getMessage();
        }
        return $this->response_json();
    }
}
