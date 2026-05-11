<?php

namespace App\Actions\Gopanel\SiteRedirect;

use App\Models\Seo\SiteRedirect;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveSiteRedirectFormAction
{
    use AsAction;

    public function handle(array $form): SiteRedirect
    {
        return DB::transaction(function () use ($form): SiteRedirect {
            $redirect = SiteRedirect::findOrNew($form['id'] ?? null);
            $redirect->fill(collect($form)->except('id')->all());
            $redirect->save();

            return $redirect;
        });
    }
}
