<?php

namespace App\Models\Site;

use App\Enums\Common\SocialIconTypeEnum;
use App\Models\BaseModel;
use App\Traits\Content\MetaData;
use App\Traits\Content\Translation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Presentable\Site\HasServicePresentation;

class Service extends BaseModel
{
    use HasFactory, SoftDeletes, Translation, MetaData, HasServicePresentation;

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
}
