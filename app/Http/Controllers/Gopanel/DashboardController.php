<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\GoPanelController;
use Exception;
use Illuminate\Http\Request;

class DashboardController extends GoPanelController
{

    public function __construct()
    {
        // code 
    }


    public function index(Request $request)
    {
        return view("gopanel/dashboard");
    }
}
