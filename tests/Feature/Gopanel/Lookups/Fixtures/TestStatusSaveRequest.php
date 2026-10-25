<?php

namespace Tests\Feature\Gopanel\Lookups\Fixtures;

use App\Http\Requests\Gopanel\Lookups\BaseLookupSaveRequest;

class TestStatusSaveRequest extends BaseLookupSaveRequest
{
    protected string $model = TestStatus::class;
}
