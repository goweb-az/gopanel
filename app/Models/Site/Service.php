<?php

namespace App\Models\Site;

use App\Enums\Common\SocialIconTypeEnum;
use App\Models\BaseModel;
use App\Traits\Content\MetaData;
use App\Traits\Content\Translation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Service extends BaseModel
{
    use HasFactory, SoftDeletes, Translation, MetaData;

    protected $table = 'services';

    protected $logEnabled = false;

    protected $fillable = [
        'sort_order',
        'icon_type',
        'icon',
        'image',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'icon_type' => SocialIconTypeEnum::class,
        'created_at' => 'datetime',
    ];

    protected $files = ['image'];

    public $translatedAttributes = ['title', 'short_description', 'description'];

    protected static function booted()
    {
        static::creating(function ($service) {
            if (is_null($service->sort_order)) {
                $max = self::max('sort_order');
                $service->sort_order = $max !== null ? $max + 1 : 0;
            }
        });
    }

    public function getShortDescriptionViewAttribute(): string
    {
        return Str::limit(strip_tags($this->short_description ?? ''), 80);
    }

    public function getImageViewAttribute(): string
    {
        if (empty($this->image_url)) {
            return '<span class="text-muted">Şəkil yoxdur</span>';
        }

        return '<img src="' . e($this->image_url) . '" alt="' . e($this->title ?? 'Service') . '" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">';
    }

    public function getIconViewAttribute(): string
    {
        if (empty($this->icon)) {
            return '<span class="text-muted">İkon yoxdur</span>';
        }

        if ($this->icon_type === SocialIconTypeEnum::Image) {
            return '<img src="' . e(asset($this->icon)) . '" alt="' . e($this->title ?? 'Service') . '" style="width:34px;height:34px;object-fit:contain;">';
        }

        if (str_starts_with(trim($this->icon), '<')) {
            return '<span style="font-size:20px;">' . $this->icon . '</span>';
        }

        return '<i class="' . e($this->icon) . '" style="font-size:20px;"></i>';
    }

    public function getIconValueAttribute(): string
    {
        return $this->icon_type === SocialIconTypeEnum::Image
            ? asset($this->icon)
            : (string) $this->icon;
    }
}
