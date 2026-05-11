<?php

namespace App\Actions\Gopanel\Language;

use App\Models\Geography\Language;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteLanguageAction
{
    use AsAction;

    public function handle(int $id): void
    {
        DB::transaction(function () use ($id): void {
            Language::findOrFail($id)->delete();
            Language::ensureFallbackDefault();
        });
    }
}
