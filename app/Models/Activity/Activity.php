<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity as BaseActivity;
use App\Traits\Presentable\Activity\HasActivityPresentation;

class Activity extends BaseActivity
{
    use HasActivityPresentation;

    /**
     * Get the model that caused the activity.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the model associated with the activity.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
