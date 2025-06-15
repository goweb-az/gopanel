<?php

namespace App\Models\Geography;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $logEnabled = false;

    protected $fillable = [
        'country_id',
        'name',
        'district',
        'state',
        'postal_code',
        'latitude',
        'longitude',
        'population',
        'area',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'area' => 'decimal:2',
        'population' => 'integer',
        'is_active' => 'boolean',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
