<?php

namespace App\Actions\Gopanel\Activity;

use App\Models\Activity\FileLog;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteFileLogAction
{
    use AsAction;

    public function handle(int $id): void
    {
        FileLog::findOrFail($id)->delete();
    }
}
