<?php

declare(strict_types=1);

namespace App\Policies;

/**
 * Bütün panel policy-lərinin əsası.
 *
 * NİYƏ `is_super` burada da yoxlanılır:
 * `AuthServiceProvider`-dəki `Gate::before` yalnız `Gate` üzərindən gedən
 * yoxlamalarda işləyir. Policy birbaşa çağırılanda (məsələn növbədə işləyən
 * job içində, `Gate::forUser(...)` ilə) həmin qayda tətbiq olunmurdu və super
 * admin öz əməliyyatından imtina cavabı alırdı. Ona görə qayda burada
 * təkrarlanır - iki yol da eyni nəticəni verir.
 */
abstract class BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user && (bool) ($user->is_super ?? false) === true) {
            return true;
        }

        return null;
    }

    /** İcazə adına görə yoxlama - `null` istifadəçidə həmişə `false`. */
    protected function can(mixed $user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        if ((bool) ($user->is_super ?? false) === true) {
            return true;
        }

        return method_exists($user, 'can') && $user->can($permission);
    }
}
