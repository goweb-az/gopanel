<?php


namespace App\Helpers\Gopanel;

use App\Helpers\Common\Singleton;
use Illuminate\Support\Collection;

class GoPanelSidebar extends Singleton
{

    /**
     *
     * @var Collection
     */
    private $list;

    protected function __construct()
    {
        parent::__construct();
        $this->list = collect([]);
    }

    /**
     * @return Collection
     */
    public function getItems()
    {
        return $this->list;
    }

    /**
     * @param array $item
     * @throws \Exception
     */
    public function addItem(array $item): void
    {
        $menuItem = $this->validateInputItems($item);

        $this->list->add($menuItem);
    }

    /**
     * @param array $items
     * @throws \Exception
     */
    public function addItems(array $items): void
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    /**
     *
     * @param array $item
     * @param bool $isRoot
     * @return Collection
     * @throws \Exception
     */
    private function validateInputItems(array $item, $isRoot = true): Collection
    {
        try {
            $menuItem = collect([
                'icon' => array_key_exists('icon', $item) ? $item['icon'] : '<i class="fas fa-dot-circle"></i>',
                'title' => $item['title'],
                'route' => array_key_exists('route', $item) ? $item['route'] : null,
                'params' => array_key_exists('params', $item) ? $item['params'] : null,
                'can' => array_key_exists('can', $item) ? $item['can'] : '*',
                'inner' => array_key_exists('inner', $item) ? $item['inner'] : null,
                'is_active_route' => $this->isActiveRoute($item),
                'target' => array_key_exists('target', $item) ? $item['target'] : '_self', // Default target is '_self'
                'guards' => array_key_exists('guards', $item) ? $item['guards'] : null, // Default target is '_self'
                'badge' => array_key_exists('badge', $item) ? $item['badge'] : null, // Default target is '_self'
            ]);

            // $menuItem->put('showFlag', $menuItem['can'] == '*' ? true : auth()->user()->can($menuItem['can']));
            $menuItem->put('showFlag', true);

            if ($isRoot and array_key_exists('inner', $item)) {
                $innerMenu = collect();
                $innerRoutes = [];
                foreach ($item['inner'] as $innerItem) {
                    $menuItem['showFlag'] = false;
                    $innerMenu->add($this->validateInputItems($innerItem, false));
                    array_push($innerRoutes, $innerItem['route']);
                }

                if ($innerMenu->where('showFlag', true)->first()) {
                    $menuItem['showFlag'] = true;
                }

                $menuItem->put('inner', $innerMenu);
                $menuItem->put('isActiveGroup', request()->routeIs($innerRoutes));
            }
            return $menuItem;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }


    public function clearItems(): void
    {
        $this->list = collect([]);
    }


    public function sidebarGuardCheck(string|null $guards): bool
    {
        if (!is_null($guards)) {
            $isConfirm = false;
            foreach (explode(',', $guards) as $guard) {
                if (auth()->guard($guard)->check()) {
                    $isConfirm = true;
                    break;
                }
            }
            return $isConfirm;
        }
        return true;
    }


    private function isActiveRoute($item)
    {
        // dd(request()->path(), request()->url(), request()->fullUrl());
        // return array_key_exists('route', $item) && request()->routeIs($item['route']);
        // return array_key_exists('route', $item) && request()->is(trim($item['route'], '/') . '*');
        // return array_key_exists('route', $item) && str_contains(request()->path(), trim($item['route'], '/'));
        // return array_key_exists('route', $item) && str_starts_with(request()->path(), trim($item['route'], '/'));
        return array_key_exists('route', $item) && str_contains(request()->path(), trim($item['route'], '/'));
    }
}
