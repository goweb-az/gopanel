<?php

namespace App\Actions\Gopanel\Translation;

use App\Models\Translations\Translation;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteTranslationAction
{
    use AsAction;

    /**
     * Delete every row that shares the same (key, platform) bundle.
     * The grid groups translations by key+platform across locales.
     */
    public function handle(string $key, string $platform): int
    {
        return Translation::where('key', $key)->where('platform', $platform)->delete();
    }
}
