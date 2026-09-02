<?php

declare(strict_types=1);

namespace App\Policies\Gopanel;

use App\Policies\CrudPolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Backup icazələri.
 *
 * Redaktə anlayışı yoxdur - arxiv yaranandan sonra dəyişdirilmir,
 * yalnız endirilir və ya silinir.
 */
class BackupPolicy extends CrudPolicy
{
    protected string $module = 'gopanel.backup';

    public function update(mixed $user, ?Model $model = null): bool
    {
        return false;
    }
}
