<?php

namespace App\Actions\Gopanel\Language;

use App\Models\Geography\Language;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveLanguageFormAction
{
    use AsAction;

    public function handle(array $form): Language
    {
        return DB::transaction(function () use ($form): Language {
            $language = Language::findOrNew($form['id'] ?? null);

            $data = collect($form)->except('id')->all();

            // Default-language guard: a default language must always be active.
            if (! empty($data['default'])) {
                $data['is_active'] = true;
            }

            $language->fill($data);
            $language->save();

            Language::ensureSingleDefault($language);
            Language::ensureFallbackDefault();

            return $language->fresh();
        });
    }
}
