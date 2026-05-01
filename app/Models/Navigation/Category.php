<?php

namespace App\Models\Navigation;

use App\Enums\Common\SocialIconTypeEnum;
use App\Models\BaseModel;
use App\Traits\Content\MetaData;
use App\Traits\Content\Translation;
use App\Traits\System\AddUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends BaseModel
{
    use AddUuid, HasFactory, SoftDeletes, Translation, MetaData;

    protected $logEnabled = false;

    /**
     * Attributes translated via field_translations.
     * name, description, slug stored via Translation trait.
     *
     * @var array
     */
    public $translatedAttributes = ['name', 'description', 'slug'];

    /**
     * The attribute used to generate slug.
     *
     * @var string
     */
    public $slug_key = 'name';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uid',
        'parent_id',
        'icon',
        'icon_type',
        'color',
        'sort_order',
        'is_active',
        'show_in_home',
        'show_in_menu',
        'home_order',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active'    => 'boolean',
        'show_in_home' => 'boolean',
        'show_in_menu' => 'boolean',
        'sort_order'   => 'integer',
        'home_order'   => 'integer',
        'icon_type'    => SocialIconTypeEnum::class,
        'created_at'   => 'datetime',
    ];

    // ──────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────

    /**
     * Parent category.
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Child categories.
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Recursive children (nested tree).
     */
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * News articles in this category (many-to-many via pivot).
     */
    public function news()
    {
        return $this->belongsToMany(News::class, 'news_categories');
    }

    public function getIconViewAttribute(): string
    {
        if (empty($this->icon)) {
            return '<i class="fas fa-tag text-muted"></i>';
        }

        if ($this->icon_type === SocialIconTypeEnum::Image) {
            return '<img src="' . e(asset($this->icon)) . '" alt="category" style="width:20px;height:20px;object-fit:contain;">';
        }

        return '<i class="' . e($this->icon) . '"></i>';
    }

    public function getIconValueAttribute(): string
    {
        return $this->icon_type === SocialIconTypeEnum::Image
            ? asset($this->icon)
            : (string) $this->icon;
    }
}
