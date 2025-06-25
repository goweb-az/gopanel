<?php

namespace App\Models\Gopanel;

use Spatie\Permission\Models\Role;

class CustomRole extends Role
{
    protected $fillable = [
        'name',
        'guard_name',
    ];


    public function getPermissionsCountAttribute(): string|null
    {
        return ' İcazələri [' . $this->permissions()->count() . ']';
    }
}
