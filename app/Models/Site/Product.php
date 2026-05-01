<?php

namespace App\Models\Site;

use App\Models\BaseModel;
use App\Traits\Content\MetaData;
use App\Traits\Content\Translation;
use App\Traits\System\AddUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends BaseModel
{
    use HasFactory, SoftDeletes, Translation, MetaData, AddUuid;

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

    public function getShortDescriptionViewAttribute(): string
    {
        return Str::limit(strip_tags($this->short_description ?? ''), 80);
    }

    public function getImageViewAttribute(): string
    {
        if (empty($this->image_url)) {
            return '<span class="text-muted">Şəkil yoxdur</span>';
        }

        return '<img src="' . e($this->image_url) . '" alt="' . e($this->title ?? 'Product') . '" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">';
    }

    public function getPriceViewAttribute(): string
    {
        return number_format((float) $this->price, 2, '.', ' ') . ' ₼';
    }

    public function getDiscountViewAttribute(): string
    {
        if (is_null($this->discount)) {
            return '<span class="text-muted">—</span>';
        }

        return '<span class="text-danger">' . number_format((float) $this->discount, 2, '.', ' ') . ' ₼</span>';
    }

    public function getFinalPriceAttribute(): float
    {
        $price = (float) $this->price;
        $discount = (float) ($this->discount ?? 0);

        return max(0, $price - $discount);
    }

    public function getPriceWithDiscountViewAttribute(): string
    {
        $price = number_format((float) $this->price, 2, '.', ' ') . ' ₼';

        if (is_null($this->discount) || (float) $this->discount <= 0) {
            return '<strong>' . $price . '</strong>';
        }

        $final = number_format($this->final_price, 2, '.', ' ') . ' ₼';

        return '<span class="text-decoration-line-through text-muted me-1">' . $price . '</span>'
            . '<strong class="text-danger">' . $final . '</strong>';
    }

    public function getIsActiveBtnAttribute(): string
    {
        return app('gopanel')->toggle_btn($this, 'is_active', $this->is_active == 1);
    }
}
