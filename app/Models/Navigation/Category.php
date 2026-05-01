<?php

namespace App\Models\Navigation;

use App\Models\BaseModel;
use App\Traits\AddUuid;
use App\Traits\MetaData;
use App\Traits\Translation;
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
}
