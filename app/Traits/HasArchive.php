<?php

namespace App\Traits;

use App\Scopes\ArchivedScope;

trait HasArchive
{
    protected static function bootHasArchive()
    {
        static::addGlobalScope(new ArchivedScope);
    }

    public function archive()
    {
        $this->update(['archived_at' => now()]);
    }

    public function restoreFromArchive()
    {
        $this->update(['archived_at' => null]);
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull($query->getTable() . 'archived_at');
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull($query->getTable() . 'archived_at');
    }
}
