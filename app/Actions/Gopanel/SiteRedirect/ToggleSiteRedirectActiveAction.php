<?php

namespace App\Actions\Gopanel\SiteRedirect;

use App\Models\Seo\SiteRedirect;
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleSiteRedirectActiveAction
{
    use AsAction;

    public function handle(int $id): SiteRedirect
    {
        $redirect = SiteRedirect::findOrFail($id);
        $redirect->is_active = ! $redirect->is_active;
        $redirect->save();

        return $redirect;
    }
}
