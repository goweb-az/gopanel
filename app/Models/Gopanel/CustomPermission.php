<?php

namespace App\Models\Gopanel;

use Spatie\Permission\Models\Permission;

class CustomPermission extends Permission
{
    protected $fillable = [
        'name',
        'title',
        'group',
        'guard_name',
    ];
}
