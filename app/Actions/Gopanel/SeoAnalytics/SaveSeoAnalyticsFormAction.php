<?php

namespace App\Actions\Gopanel\SeoAnalytics;

use App\Models\Seo\SeoAnalytics;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveSeoAnalyticsFormAction
{
    use AsAction;

    public function handle(array $form): SeoAnalytics
    {
        return DB::transaction(function () use ($form): SeoAnalytics {
            $item = SeoAnalytics::findOrNew($form['id'] ?? null);
            $item->fill(collect($form)->except('id')->all());
            $item->save();

            Cache::forget('seo_analytics');

            return $item;
        });
    }
}
