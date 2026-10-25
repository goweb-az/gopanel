<?php

namespace Tests\Feature\Gopanel\Authors\Fixtures;

use App\Models\BaseModel;
use App\Traits\System\HasAuthors;

/** `HasAuthors`-a qoşulmuş (opt-in) test modeli. */
class TestTicket extends BaseModel
{
    use HasAuthors;

    protected $table = 'test_tickets';

    protected $fillable = ['title'];
}
