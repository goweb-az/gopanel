<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class GopanelFacades extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'gopanel';
    }
}
