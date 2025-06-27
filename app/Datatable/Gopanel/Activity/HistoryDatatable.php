<?php

namespace App\Datatable\Gopanel\Activity;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Activity\Activity;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HistoryDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(Activity::class, [
            'id' => 'ID',
            'event_name'        => 'Əməliyyat',
            'causer_name_link'  => 'Kim tərəfindən',
            'description'       => 'Məlumat',
            'log_name_link'     => 'Model',
            'created_at'        => 'Tarix'
        ], [
            [
                'title' => 'Əməliyyatlar',
                'type' => 'callable',
                'view' => function ($item) {
                    return $this->itemActions($item);
                }
            ],
        ]);
    }

    protected function query(): Builder
    {
        $query = $this->baseQueryScope();

        if (request()->has("causer_id") and request()->causer_id >= 0) {
            $query->where('causer_id', request()->causer_id);
        }

        if (request()->has("subject_id") and request()->subject_id >= 0) {
            $query->where('subject_id', request()->subject_id);
        }

        if (request()->has("event") and !empty(request()->event)) {
            $query->where('event', request()->event);
        }

        if (request()->has("log_name") and !empty(request()->log_name)) {
            $query->where('log_name', request()->log_name);
        }

        if (request()->has('from') || request()->has('to')) {
            $from = request()->get('from');
            $to = request()->get('to');
            if (!is_null($from) && !is_null($to)) {
                $query->whereBetween('created_at', [$from, $to]);
            } elseif (!is_null($from)) {
                $query->where('created_at', '>=', $from);
            } elseif (!is_null($to)) {
                $query->where('created_at', '<=', $to);
            }
        }

        if ($this->getSearchInput()) {
            $searchInput = strtolower($this->getSearchInput());
            $query->where(function ($q) use ($searchInput) {
                $q->orWhere('log_name', 'LIKE', "%{$searchInput}%");
                $q->orWhere('description', 'LIKE', "%{$searchInput}%");
                $q->orWhere('subject_type', 'LIKE', "%{$searchInput}%");
                $q->orWhere('causer_type', 'LIKE', "%{$searchInput}%");
                $q->orWhere('properties', 'LIKE', "%{$searchInput}%");
            });
        }
        return $query;
    }


    private function itemActions(Model $item): string
    {
        $view = '';
        $view .= $this->itemDeleteBtn($item);
        return '<div class="actions text-center">' . $view . '</div>';
    }

    private function itemDeleteBtn(Model $item, $url = null)
    {
        $url = route('gopanel.activity.history.show', $item->id);
        return '<a  class="btn btn-outline-primary waves-effect waves-light show-history" data-url="' . $url . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Ətarflı bax">
                    <i class="fas fa-eye"></i>
                </a> ';
    }
}
