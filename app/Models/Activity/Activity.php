<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    /**
     * Get the model that caused the activity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the model associated with the activity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
