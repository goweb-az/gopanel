<?php

namespace App\Http\Controllers\Site;

use App\Models\Translations\FieldTranslation;
use App\Services\Site\ContentResolver;
use App\Services\Site\Seo\SlugAliasResolver;

class DynamicContentController extends SiteController
{


    public function __construct()
    {
        parent::__construct();
    }


    public function index(string $slug)
    {
        $data = FieldTranslation::getBySlug($slug);

        if (!$data || empty($data?->model)) {
            // Yanlış dildəki slug (`/en/bloqlar`) 404 əvəzinə düzgün dilin
            // ünvanına 301 alır - köhnə linklər və əl ilə dəyişdirilmiş dil
            // prefiksi itmir (bax SlugAliasResolver)
            $corrected = app(SlugAliasResolver::class)->correctPath(app()->getLocale(), [$slug]);

            if ($corrected !== null) {
                return redirect()->to(url($corrected), 301);
            }
        }

        $this->validation($data);
        $model  = $data->model;
        return app(ContentResolver::class)->handle($model);
    }


    private function validation($data)
    {
        if (!$data) {
            abort(404);
        }
        if (empty($data?->model)) {
            abort(404);
        }
    }
}
