<?php

namespace App\Datatable\Gopanel\Analytics;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Analytics\AnalyticsUtmParameter;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UtmParameterDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(AnalyticsUtmParameter::class, [
            'id'                => 'ID',
            'click.link.url'    => 'Klik',
            'utm_source'        => 'utm_source',
            'utm_medium'        => 'utm_medium',
            'utm_campaign'      => 'utm_campaign',
            'utm_term'          => 'utm_term',
            'utm_content'       => 'utm_content',
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


    private function itemActions(Model $item): string
    {
        return '';
    }
}
