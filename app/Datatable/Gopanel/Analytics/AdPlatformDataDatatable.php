<?php

namespace App\Datatable\Gopanel\Analytics;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Analytics\AnalyticsAdPlatform;
use App\Models\Analytics\AnalyticsAdPlatformData;
use App\Models\Analytics\AnalyticsLink;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdPlatformDataDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(AnalyticsAdPlatformData::class, [
            'id'                => 'ID',
            'click.link.url'    => 'Link',
            'platform.name'     => 'platform_id',
            'param_key'         => 'param_key',
            'param_value'       => 'param_value',
        ], [
            // 'actions' => [
            //     'title' => 'Əməliyyatlar',
            //     'type'  => 'callable',
            //     'view'  => function ($item) {
            //         return $this->itemActions($item);
            //     }
            // ],
        ]);
    }


    protected function query(): Builder
    {
        $query  = $this->baseQueryScope();

        return $query;
    }
}
