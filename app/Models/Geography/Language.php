<?php

namespace App\Models\Geography;

use App\Models\BaseModel;
use App\Traits\AddUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Language extends BaseModel
{
    use AddUuid, HasFactory, SoftDeletes;

    protected $logEnabled = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_id',
        'code',
        'name',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Relationship with Country
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }


    public function getUpperCodeAttribute()
    {
        return mb_strtoupper($this->code, 'UTF-8');
    }


    public static function getCachedAll()
    {
        $instance = new static();
        return Cache::remember("site_" . $instance->getTable(), now()->addDays(5), function () use ($instance) {
            $query = $instance->newQuery();
            $query->where("is_active", true);
            $query->orderBy("id", "DESC");
            return $query->get();
        });
    }
}
