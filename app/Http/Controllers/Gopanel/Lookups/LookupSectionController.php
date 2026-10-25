<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gopanel\Lookups;

use App\Http\Controllers\GoPanelController;
use App\Services\Gopanel\Lookup\LookupRegistry;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * «Status və Tiplər» bölməsinin giriş nöqtəsi (`gopanel.lookups.index`).
 *
 * NİYƏ: əsas sol menyuda tiplərin hər biri ayrıca sətir olsa, 10 lookup-lu
 * layihədə menyu uzanır (spec § 2). Menyu bir sətirdir, bu route isə adminin
 * görə bildiyi İLK tipə yönləndirir; qalanları bölmədaxili sidebar-dadır.
 */
class LookupSectionController extends GoPanelController
{
    public function index(): RedirectResponse
    {
        $admin = auth('gopanel')->user();

        foreach (array_keys(LookupRegistry::types()) as $type) {
            if ($admin !== null && $admin->can(LookupRegistry::permission($type, 'index'))) {
                return redirect()->route(LookupRegistry::routeName($type) . '.index');
            }
        }

        throw new AccessDeniedHttpException('Bu bölmə üçün icazəniz yoxdur.');
    }
}
