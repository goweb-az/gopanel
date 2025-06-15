<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Site\SiteService;

class GoPanelController extends Controller
{
    public SiteService $siteService;

    public function __construct()
    {
        parent::__construct();
        $this->siteService = new SiteService();
        $this->response['redirect'] = false;
    }
}
