<?php

namespace App\View\Components\Gopanel;

use Illuminate\View\Component;

class Footer extends Component
{
    public string $appName;
    public int $year;

    public function __construct()
    {
        $this->appName = (string) env('APP_NAME', 'PROWEB');
        $this->year = (int) date('Y');
    }

    public function render()
    {
        return view('components.gopanel.footer');
    }
}
