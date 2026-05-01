<?php

namespace App\Models\Site;

use App\Models\BaseModel;
use App\Traits\MetaData;
use App\Traits\Translation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AboutUs extends BaseModel
{
    use HasFactory, Translation, MetaData;

    protected $table = 'about_us';

    protected $logEnabled = false;

    protected $fillable = [
        'image',
    ];

    protected $files = ['image'];

    public $translatedAttributes = ['title', 'description'];
}
