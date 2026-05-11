<?php

namespace App\Actions\Gopanel\Language;

use App\Models\Geography\Language;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleLanguageDefaultAction
{
    use AsAction;

    public function handle(int $id): Language
    {
        return DB::transaction(function () use ($id): Language {
            $record = Language::findOrFail($id);

            if ($record->default) {
                $record->forceFill(['default' => false])->save();
                Language::ensureFallbackDefault();
            } else {
                Language::query()->update(['default' => false]);
                $record->forceFill(['default' => true, 'is_active' => true])->save();
            }

            return $record;
        });
    }
}
