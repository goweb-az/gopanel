<?php

namespace Tests\Feature\Gopanel\Lookups\Fixtures;

use App\Models\BaseModel;
use App\Traits\Content\Translation;
use App\Traits\System\AddUuid;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Yalnız test üçün lookup modeli - starter real lookup daşımır,
 * ona görə ortaq qat bu atılan model üzərində yoxlanılır.
 */
class TestStatus extends BaseModel
{
    use AddUuid, SoftDeletes, Translation;

    protected $table = 'test_statuses';

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = ['uid', 'key', 'color', 'class_name', 'sort_order', 'is_default', 'is_public', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_default' => 'boolean',
        'is_public'  => 'boolean',
        'is_active'  => 'boolean',
    ];
}
