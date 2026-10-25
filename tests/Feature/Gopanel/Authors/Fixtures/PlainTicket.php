<?php

namespace Tests\Feature\Gopanel\Authors\Fixtures;

use App\Models\BaseModel;

/** Eyni cədvəl, amma `HasAuthors` YOXDUR - opt-in olduğunu yoxlamaq üçün. */
class PlainTicket extends BaseModel
{
    protected $table = 'test_tickets';

    protected $fillable = ['title'];
}
