<?php

namespace App\View\Components\Gopanel;

use App\Models\Settings\SiteSetting;
use Illuminate\View\Component;

class Header extends Component
{
    public ?SiteSetting $settings;

    public function __construct()
    {
        $this->settings = SiteSetting::latest()->first();
    }

    public function render()
    {
        return view('components.gopanel.header');
    }
}
