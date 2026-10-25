<?php

namespace App\Models\Site;

use App\Models\BaseModel;
use App\Traits\Content\MetaData;
use App\Traits\Content\Translation;
use App\Traits\System\AddUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Presentable\Site\HasProductPresentation;

class Product extends BaseModel
{
    use HasFactory, SoftDeletes, Translation, MetaData, AddUuid, HasProductPresentation;

    protected $table = 'products';

    protected $logEnabled = false;

    protected $fillable = [
        'uid',
        'price',
        'discount',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'discount'   => 'decimal:2',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
    ];

    protected $files = ['image'];

    public $translatedAttributes = ['title', 'short_description', 'description', 'slug'];

    public $slug_key = 'title';
}
