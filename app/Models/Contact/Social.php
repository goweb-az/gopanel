<?php

namespace App\Models\Contact;

use App\Enums\Common\SocialIconTypeEnum;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Social extends BaseModel
{
    use HasFactory, SoftDeletes;

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


    public function getIconHtmlAttribute(): string
    {
        if ($this->icon_type === SocialIconTypeEnum::Image) {
            $url = asset($this->icon);
            return '<img src="' . e($url) . '" alt="' . e($this->name) . '">';
        }

        if (in_array($this->icon_type->value, SocialIconTypeEnum::values())) {
            return $this->icon; // SVG, FONT ve STRING doğrudan döndürülür (zaten HTML veya yazıdır)
        }

        return '';
    }

    public function getIconValueAttribute(): string
    {
        return $this->icon_type === SocialIconTypeEnum::Image
            ? asset($this->icon)
            : $this->icon;
    }

    public static function getCached(): ?Collection
    {
        return Cache::rememberForever('social_links', function () {
            return self::where("is_active", true)->get();
        });
    }
}
