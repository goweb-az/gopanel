<?php

namespace App\Models\Contact;

use App\Enums\Common\SocialIconTypeEnum;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use App\Traits\Presentable\Contact\HasSocialPresentation;

class Social extends BaseModel
{
    use HasFactory, SoftDeletes, HasSocialPresentation;

    protected $fillable = [
        'name',
        'icon',
        'icon_type',
        'url',
        'target_blank',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'target_blank'  => 'boolean',
        'icon_type'     => SocialIconTypeEnum::class,
    ];

    protected static function booted()
    {
        static::addGlobalScope('sort_order', function ($query) {
            $query->orderBy('sort_order', 'asc');
        });
    }

    public static function getCached(): ?Collection
    {
        return Cache::rememberForever('social_links', function () {
            return self::where("is_active", true)->get();
        });
    }
}
