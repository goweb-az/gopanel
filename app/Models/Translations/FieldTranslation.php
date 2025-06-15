<?php

namespace App\Models\Translations;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class FieldTranslation extends BaseModel
{

    use HasFactory, SoftDeletes;

    protected $logEnabled = false;

    protected $fillable = [
        'model_type',
        'model_id',
        'locale',
        'key',
        'value'
    ];


    public function model()
    {
        return $this->morphTo();
    }
}
