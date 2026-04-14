<?php

namespace App\Http\Controllers\Site\Seo;

use App\Http\Controllers\Site\SiteController;
use App\Models\Seo\SeoAnalytics;
use Illuminate\Http\Request;

class TxtController extends SiteController
{
    public $seoAnalytics;

    public function __construct()
    {
        parent::__construct();
        $this->seoAnalytics = SeoAnalytics::getCached();
    }

    public function robots(Request $request)
    {
        return response($this->seoAnalytics->robots_txt, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function ai(Request $request)
    {
        return response($this->seoAnalytics->ai_txt, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function llms(Request $request)
    {
        $llms = \App\Models\Seo\LlmsTxt::getCached();
        return response($llms->content ?? '', 200)
            ->header('Content-Type', 'text/plain');
    }
}
