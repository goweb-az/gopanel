<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Database\Eloquent\Model;

/**
 * Standart CRUD policy-si.
 *
 * NİYƏ ümumi sinif:
 * Panelin icazə adları qaydalıdır (`gopanel.<modul>.index|add|edit|delete` -
 * bax .claude/rules/01-umumi.md § 5), ona görə hər modul üçün eyni dörd metodu
 * yenidən yazmaq mənasızdır. Alt sinifdə yalnız `$module` elan olunur:
 *
 *     class BlogPolicy extends CrudPolicy
 *     {
 *         protected string $module = 'gopanel.blog';
 *     }
 *
 * Laravel qabiliyyətləri → icazələr:
 *   viewAny / view → `.index`
 *   create         → `.add`
 *   update         → `.edit`
 *   delete         → `.delete`
 *
 * Xüsusi qayda lazımdırsa (öz-özünü silmə, super admini silmə və s.) alt
 * sinifdə həmin metod override edilir.
 */
abstract class CrudPolicy extends BasePolicy
{
    /** İcazə prefiksi, məs. `gopanel.blog`. */
    protected string $module = '';

    public function viewAny(mixed $user): bool
    {
        return $this->can($user, $this->permission('index'));
    }

    public function view(mixed $user, ?Model $model = null): bool
    {
        return $this->can($user, $this->permission('index'));
    }

    public function create(mixed $user): bool
    {
        return $this->can($user, $this->permission('add'));
    }

    public function update(mixed $user, ?Model $model = null): bool
    {
        return $this->can($user, $this->permission('edit'));
    }

    public function delete(mixed $user, ?Model $model = null): bool
    {
        return $this->can($user, $this->permission('delete'));
    }

    /** Sıralama, status dəyişmə kimi sətir əməliyyatları redaktə icazəsi tələb edir. */
    public function manage(mixed $user, ?Model $model = null): bool
    {
        return $this->can($user, $this->permission('edit'));
    }

    protected function permission(string $action): string
    {
        return $this->module . '.' . $action;
    }
}
