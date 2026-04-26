<?php

namespace App\Datatable\Gopanel\Analytics;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Analytics\AnalyticsBrowser;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BrowserDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(
            AnalyticsBrowser::class,
            [
                'id'                => 'ID',
                'icon_img'          => 'icon',
                'name'              => 'name',
                'hit_count'         => 'Toplam Giriş',
                'first_visited_at'  => 'İlk Giriş',
                'last_visited_at'   => 'Son giriş',
            ],
            [
                // 'actions' => [
                //     'title' => 'Əməliyyatlar',
                //     'type'  => 'callable',
                //     'view'  => function ($item) {
                //         return $this->itemActions($item);
                //     }
                // ],
            ]
        );
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

    protected function order(Builder $query, string $columnName, string $columnSort): void
    {
        // Default sorting is by the selected column and sort direction.
        $query->orderBy("hit_count", "DESC");
    }
}
