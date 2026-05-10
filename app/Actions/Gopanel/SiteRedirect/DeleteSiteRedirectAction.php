<?php

namespace App\Actions\Gopanel\SiteRedirect;

use App\Models\Seo\SiteRedirect;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteSiteRedirectAction
{
    use AsAction;

    public function handle(int $id): void
    {
        SiteRedirect::findOrFail($id)->delete();
    }
}
