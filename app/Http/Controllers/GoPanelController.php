<?php

namespace App\Http\Controllers;

use App\Helpers\Gopanel\Site\GoPanelSiteHelper;
use App\Http\Controllers\Controller;
use App\Services\Site\SiteService;

class GoPanelController extends Controller
{
    public SiteService $siteService;
    public GoPanelSiteHelper $gopanelHelper;

    public function __construct()
    {
        parent::__construct();
        $this->siteService = new SiteService();
        $this->gopanelHelper = new GoPanelSiteHelper();
        $this->response['redirect'] = false;
    }
}
