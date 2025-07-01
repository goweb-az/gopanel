<?php

namespace App\Services\Site;



use App\Services\Activity\LogService;
use Exception;


class SiteService
{

    public $logging;

    public function __construct()
    {
        $this->logging = new LogService("gopanel");
    }

    public function share(array $data)
    {
        return view()->share($data);
    }
}
