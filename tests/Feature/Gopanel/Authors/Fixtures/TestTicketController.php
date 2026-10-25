<?php

namespace Tests\Feature\Gopanel\Authors\Fixtures;

use App\Http\Controllers\GoPanelController;
use App\Http\Controllers\Gopanel\Concerns\HandlesAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Bölmə controller-inin minimal forması - sənəddəki nümunə ilə eynidir. */
class TestTicketController extends GoPanelController
{
    use HandlesAssignment;

    public function assign(Request $request, TestTicket $item): JsonResponse
    {
        return $this->applyAssignment($request, $item);
    }

    public function release(TestTicket $item): JsonResponse
    {
        return $this->applyRelease($item);
    }

    protected function assignmentRoutePrefix(): string
    {
        return 'gopanel.test-tickets';
    }
}
