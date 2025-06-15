<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataUpdate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_id',
        'company_id',
        'old_value',
        'new_value',
        'is_updated',
    ];

    protected $appends = ['is_updated_status'];

    public function updatable()
    {
        return $this->morphTo();
    }

    // // Bashqa modelelrde bele yazilmalidir
    // public function dataUpdates()
    // {
    //     return $this->morphMany(DataUpdate::class, 'updatable');
    // }

    public function getIsUpdatedStatusAttribute()
    {
        return (bool) $this->is_updated;
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
