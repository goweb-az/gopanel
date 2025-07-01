<?php

namespace App\Services;

use App\Services\Activity\LogService;


class CommonService
{

    public $logging;

    public function __construct()
    {
        $this->logging = new LogService();
    }

    public function share(array $data)
    {
        return view()->share($data);
    }
}
