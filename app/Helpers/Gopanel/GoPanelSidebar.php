<?php


namespace App\Helpers\Gopanel;

use App\Helpers\Common\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

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
        $item = $this->withExistingRoutesOnly($item);

        if ($item === null) {
            return;
        }

        $menuItem = $this->validateInputItems($item);

        $this->list->add($menuItem);
    }

    /**
     * Qeydiyyatdan keçməmiş route-a işarə edən bəndi menyudan çıxarır.
     *
     * Niyə: sidebar blade-i hər bənd üçün `route()` çağırır - konfiqurasiyada
     * hələ yazılmamış (və ya törəmə layihədə silinmiş) modulun route-u qalsa
     * `RouteNotFoundException` BÜTÜN paneli 500-ə salır. Bənd sadəcə görünmür.
     *
     * Alt bəndlərin hamısı düşsə qrup başlığı da göstərilmir. Route-suz kök
     * bəndlər (bölmə başlıqları) toxunulmur.
     *
     * @return array|null  null = bənd göstərilmir
     */
    private function withExistingRoutesOnly(array $item): ?array
    {
        if (!$this->isTurnedOn($item)) {
            return null;
        }

        if (array_key_exists('inner', $item) && is_array($item['inner'])) {
            $item['inner'] = array_values(array_filter(
                $item['inner'],
                fn ($inner) => is_array($inner)
                    && !empty($inner['route'])
                    && Route::has($inner['route'])
                    && $this->isTurnedOn($inner)
            ));

            return $item['inner'] === [] ? null : $item;
        }

        if (!empty($item['route']) && !Route::has($item['route'])) {
            return null;
        }

        return $item;
    }

    /**
     * `when` açarı konfiqurasiya bayrağına işarə edir (məs. `custom.sms.enabled`).
     *
     * Söndürülmüş modulun bəndi menyuda görünmür. İcazə ilə qarışdırılmır:
     * super admin icazələrdən azaddır, bayraq isə hamıya aiddir.
     */
    private function isTurnedOn(array $item): bool
    {
        $flag = $item['when'] ?? null;

        return $flag === null || (bool) config($flag, false);
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

            /*
             * Menyu sətri yalnız icazəsi olan adminə görünür (əvvəl yoxlama
             * söndürülmüşdü - hər admin bütün menyunu görürdü, keçid isə 403 verirdi).
             * Server tərəf yenə də route-da yoxlayır - bu yalnız görünüşdür.
             */
            $menuItem->put('showFlag', $this->canSee($menuItem['can']));

            if ($isRoot and array_key_exists('inner', $item)) {
                $innerMenu = collect();
                $innerRoutes = [];
                foreach ($item['inner'] as $innerItem) {
                    $innerMenu->add($this->validateInputItems($innerItem, false));
                    array_push($innerRoutes, $innerItem['route']);
                }

                // Qrup başlığı görünən ən azı bir alt sətir varsa açılır. Qrupun öz
                // `can`-ı (gopanel.seo.index) burada tələb olunmur: bəzi qrup
                // icazələri permission_list-də yoxdur və heç bir rola verilə bilmir.
                $menuItem['showFlag'] = (bool) $innerMenu->where('showFlag', true)->first();

                $menuItem->put('inner', $innerMenu);
                $menuItem->put('isActiveGroup', request()->routeIs($innerRoutes));
            }
            return $menuItem;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }


    /**
     * Menyu sətrinin icazəsi.
     *
     * - `*` / boş → hamıya açıq (başlıq sətirləri);
     * - `is_super` admin `Gate::before` ilə hər şeyi keçir;
     * - `config/gopanel/permission_list.php`-də QEYDİYYATDA OLMAYAN icazə gizlətmir:
     *   belə icazəni heç bir rola vermək mümkün deyil, gizlətsək sətir super
     *   admindən başqa hamıdan itər (məs. dashboard `gopanel.index`).
     *
     * Yoxlama açıq şəkildə `gopanel` guard-ı ilə aparılır - default guard `web`-dir.
     */
    private function canSee(?string $permission): bool
    {
        if (empty($permission) || $permission === '*') {
            return true;
        }

        if (!in_array($permission, $this->registeredPermissions(), true)) {
            return true;
        }

        return (bool) auth('gopanel')->user()?->can($permission);
    }

    /** @return array<int, string> */
    private function registeredPermissions(): array
    {
        static $names = null;

        return $names ??= collect(config('gopanel.permission_list.gopanel', []))
            ->flatten(1)
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
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
