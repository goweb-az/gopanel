<?php

namespace App\Models\Site;

use App\Models\BaseModel;
use App\Traits\Translation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slider extends BaseModel
{
    use SoftDeletes, HasFactory, Translation;

    protected $table = 'sliders';

    protected $fillable = [
        'id',
        'link',
        'sort_order',
        'is_active',
        'image',
    ];

    protected $logEnabled = false;


    protected $files = ['image'];

    public $translatedAttributes = ['title', 'description', 'link_title'];


    protected static function booted()
    {
        static::creating(function ($slider) {
            if (is_null($slider->sort_order)) {
                $max = self::max('sort_order');
                $slider->sort_order = $max !== null ? $max + 1 : 0;
            }
        });
    }
}
