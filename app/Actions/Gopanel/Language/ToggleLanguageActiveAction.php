<?php

namespace App\Actions\Gopanel\Language;

use App\Models\Geography\Language;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

class ToggleLanguageActiveAction
{
    use AsAction;

    /**
     * Toggle the is_active flag. Refuses to deactivate the default language.
     *
     * @return Language|null  Returns null if the toggle was rejected (default + active).
     */
    public function handle(int $id): ?Language
    {
        $language = Language::findOrFail($id);

        if ($language->default && $language->is_active) {
            return null;
        }

        $language->is_active = ! $language->is_active;
        $language->save();

        return $language;
    }
}
