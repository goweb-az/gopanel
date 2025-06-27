<?php

namespace App\Http\Controllers\Gopanel\Activity;

use App\Http\Controllers\GoPanelController;
use App\Models\Activity\FileLog;
use Exception;
use Illuminate\Http\Request;

class FileLogsController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index(Request $request)
    {
        $from               = $request->from;
        $to                 = $request->to;
        $level              = $request->level;
        $channel            = $request->channel;
        $levels             = config("custom.logging.levels");
        $channels           = collect(config("logging.channels"))
            ->filter(function ($channel) {
                return isset($channel['manual']) && $channel['manual'] === true;
            });
        return view("gopanel.pages.activity.file_logs.index", compact(
            'from',
            'to',
            'levels',
            'level',
            'channels',
            'channel',
        ));
    }

    public function show(FileLog $log, Request $request)
    {

        try {
            $view       = view('gopanel.pages.activity.file_logs.inc.show', compact('log'))->render();
            $this->response['html']             = $view;
            $this->response['message']          = "Success view";
            $this->response['status']           = "success";
            $this->response['data']             = $log?->toArray();
            $this->response['data_id']          = '#' . $log->id;
            $this->response_code                = 200;
        } catch (\Exception $e) {
            $this->response['message'] = $e->getMessage();
        }
        return $this->response_json();
    }
}
