<?php

namespace App\Models\Seo;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PageMetaData extends BaseModel
{
    use SoftDeletes;

    protected $logEnabled = false;

    protected $table = 'page_meta_data';

    protected $fillable = [
        'model_type',
        'model_id',
        'source',
        'locale',
        'title',
        'description',
        'keywords',
        'image',
    ];

    public function model()
    {
        return $this->morphTo();
    }
}
