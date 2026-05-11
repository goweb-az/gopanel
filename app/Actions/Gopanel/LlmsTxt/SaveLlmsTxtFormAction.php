<?php

namespace App\Actions\Gopanel\LlmsTxt;

use App\Models\Seo\LlmsTxt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveLlmsTxtFormAction
{
    use AsAction;

    public function handle(array $form): LlmsTxt
    {
        return DB::transaction(function () use ($form): LlmsTxt {
            $item = LlmsTxt::findOrNew($form['id'] ?? null);
            $item->fill(collect($form)->except('id')->all());
            $item->save();

            Cache::forget('llms_txt');

            return $item;
        });
    }
}
