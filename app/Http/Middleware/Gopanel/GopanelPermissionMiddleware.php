<?php

declare(strict_types=1);

namespace App\Http\Middleware\Gopanel;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GoPanel route-larında SERVER TƏRƏFİ icazə yoxlaması.
 *
 * NİYƏ LAZIMDIR:
 * `config/gopanel/permission_list.php` icazələri əsasən sidebar və blade
 * `@can` düymələrini gizlətmək üçün işlədilirdi, `gopanel` middleware-i isə
 * yalnız «login olub?» yoxlayır. Düyməni görməyən admin URL-i bilirsə
 * endpoint-i birbaşa çağıra bilirdi (bax: 01-umumi.md § 5 - «düymənin
 * gizlədilməsi tək müdafiə deyil»).
 *
 * YENİ İCAZƏ MOTORU QURULMUR:
 * Mənbə eynidir - Spatie `HasRoles` + `permission_list.php` (PermissionSeeder
 * onları `guard_name = gopanel` ilə yazır). Bu sinif yalnız mövcud
 * `$admin->can()` çağırışını route səviyyəsinə gətirir. `is_super` admin-lər
 * `AuthServiceProvider`-dəki `Gate::before` sayəsində onsuz da keçir.
 *
 * Laravel-in `can:` middleware-indən fərqi - BİR NEÇƏ İCAZƏ «HƏR HANSI BİRİ»:
 *
 *     ->middleware('gopanel.can:gopanel.lookups.order-statuses.add,gopanel.lookups.order-statuses.edit')
 *
 * Səbəb: forma endpoint-i həm yaratma, həm redaktə üçün işlədilir - yalnız
 * `add` icazəsi olan admin yaratma formasını aça bilməlidir. `can:` isə
 * vergüldən sonrakıları model arqumenti kimi oxuyur.
 *
 * Mənbə: aquastores layihəsindəki eyni adlı sinif (dəyişmədən köçürülüb).
 */
class GopanelPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$abilities)
    {
        $admin = Auth::guard('gopanel')->user();

        if ($admin === null) {
            // Normalda bura düşmür: `gopanel` middleware-i əvvəl işləyir və
            // login səhifəsinə yönləndirir. Müdafiə olaraq saxlanılır.
            throw new AccessDeniedHttpException('Bu əməliyyat üçün icazəniz yoxdur.');
        }

        foreach ($this->flatten($abilities) as $ability) {
            if ($admin->can($ability)) {
                return $next($request);
            }
        }

        // 403 - 404 DEYİL: panel daxili sistemdir, adminə problemin icazə
        // olduğu aydın görünməlidir.
        throw new AccessDeniedHttpException('Bu əməliyyat üçün icazəniz yoxdur.');
    }

    /**
     * `gopanel.can:a,b` və `gopanel.can:a|b` - hər iki yazılış dəstəklənir.
     *
     * @param  string[]  $abilities
     * @return string[]
     */
    private function flatten(array $abilities): array
    {
        $flat = [];

        foreach ($abilities as $ability) {
            foreach (preg_split('/[|,]/', $ability) as $single) {
                $single = trim((string) $single);

                if ($single !== '') {
                    $flat[] = $single;
                }
            }
        }

        return $flat;
    }
}
