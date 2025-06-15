<?php

namespace App\Models\Geography;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends BaseModel
{
    use HasFactory;

    protected $logEnabled = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'phone',
        'symbol',
        'capital',
        'currency',
        'continent',
        'continent_code',
        'alpha_3',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
