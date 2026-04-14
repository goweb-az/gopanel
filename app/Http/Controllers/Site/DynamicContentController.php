<?php

namespace App\Http\Controllers\Site;

use App\Models\Site\Blog;
use App\Models\Translations\FieldTranslation;
use App\Services\Site\ContentResolver;
use Illuminate\Http\Request;

class DynamicContentController extends SiteController
{


    public function __construct()
    {
        parent::__construct();
    }


    public function index(string $slug)
    {
        $data = FieldTranslation::getBySlug($slug);
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
