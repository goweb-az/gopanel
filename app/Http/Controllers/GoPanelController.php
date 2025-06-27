<?php

namespace App\Http\Controllers;

use App\Helpers\Gopanel\CrudHelper;
use App\Helpers\Gopanel\GoPanelHelper;
use App\Http\Controllers\Controller;
use App\Services\Site\SiteService;

class GoPanelController extends Controller
{
    public SiteService $siteService;
    public GoPanelHelper $gopanelHelper;
    public CrudHelper $crudHelper;

    public function __construct()
    {
        parent::__construct();
        $this->siteService          = new SiteService();
        $this->gopanelHelper        = new GoPanelHelper();
        $this->crudHelper           = new CrudHelper();
        $this->response['redirect'] = false;
    }
}
