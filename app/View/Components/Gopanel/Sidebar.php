<?php

namespace App\View\Components\Gopanel;

use App\Helpers\Gopanel\GoPanelSidebar;
use Illuminate\View\Component;

class Sidebar extends Component
{
    public iterable $items;

    public function __construct()
    {
        $sidebar = GoPanelSidebar::getInstance();
        $sidebar->addItems(config('gopanel.sidebar_menu_list'));
        $this->items = $sidebar->getItems();
    }

    public function render()
    {
        return view('components.gopanel.sidebar');
    }
}
