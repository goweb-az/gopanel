<?php

namespace App\Actions\Gopanel\Translation;

use App\Models\Translations\Translation;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveTranslationFormAction
{
    use AsAction;

    /**
     * Persist one Translation row per locale for a (key, platform) bundle.
     * If the user renamed key/platform, the previous bundle is dropped first
     * so updateOrCreate keys do not orphan stale rows.
     *
     * @param  array{key:string,platform:string,group:string,filename:string}  $form
     * @param  array<string,string>  $values  locale => translated value
     * @param  string|null  $originalKey
     * @param  string|null  $originalPlatform
     */
    public function handle(
        array $form,
        array $values,
        ?string $originalKey = null,
        ?string $originalPlatform = null,
    ): void {
        DB::transaction(function () use ($form, $values, $originalKey, $originalPlatform): void {
            $renamed = ($originalKey && $originalKey !== $form['key'])
                || ($originalPlatform && $originalPlatform !== $form['platform']);

            if ($renamed) {
                Translation::where('key', $originalKey)
                    ->where('platform', $originalPlatform)
                    ->delete();
            }

            foreach ($values as $locale => $value) {
                Translation::updateOrCreate(
                    [
                        'key'      => $form['key'],
                        'platform' => $form['platform'],
                        'locale'   => $locale,
                    ],
                    [
                        'value'    => $value,
                        'group'    => $form['group'],
                        'filename' => $form['filename'] ?: null,
                    ]
                );
            }
        });
    }
}
