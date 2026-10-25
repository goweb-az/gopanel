<?php

namespace Tests\Feature\Gopanel\Lookups\Fixtures;

use App\Http\Controllers\Gopanel\Lookups\BaseLookupController;

class TestStatusController extends BaseLookupController
{
    protected string $model = TestStatus::class;

    protected string $request = TestStatusSaveRequest::class;

    protected array $extraFields = ['color', 'class_name'];

    protected array $booleans = ['is_default', 'is_public', 'is_active'];

    protected array $usedBy = ['test_orders' => 'test_status_id'];
}
