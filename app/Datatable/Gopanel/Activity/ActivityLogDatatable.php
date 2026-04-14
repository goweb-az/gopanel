<?php

namespace App\Datatable\Gopanel\Activity;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Activity\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityLogDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(Activity::class, [
            'id'                   => 'ID',
            'log_name_badge'       => 'Model',
            'event_badge'          => 'Əməliyyat',
            'description_short'    => 'Mesaj',
            'causer_name'          => 'Kim tərəfindən',
            'created_at_formatted' => 'Tarix',
        ], [
            'actions' => [
                'title' => 'Əməliyyatlar',
                'type'  => 'callable',
                'view'  => function ($item) {
                    return $this->itemActions($item);
                }
            ],
        ]);
    }

    protected function query(): Builder
    {
        $query = $this->baseQueryScope()->with('causer');

        if (request('log_name')) {
            $query->where('log_name', request('log_name'));
        }

        if (request('event')) {
            $query->where('event', request('event'));
        }

        if (request('causer_id')) {
            $query->where('causer_id', request('causer_id'))
                ->where('causer_type', 'App\Models\User\User');
        }

        if (request('date_from')) {
            $query->where('created_at', '>=', request('date_from') . ' 00:00:00');
        }

        if (request('date_to')) {
            $query->where('created_at', '<=', request('date_to') . ' 23:59:59');
        }

        if ($search = $this->getSearchInput()) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                    ->orWhere('log_name', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    protected function order(Builder $query, string $columnName, string $columnSort): void
    {
        $query->orderBy("id", "DESC");
    }

    private function itemActions(Model $item): string
    {
        $view = '';
        if (auth("gopanel")->user()->can("gopanel.activity.activity-logs.view")) {
            $view .= $this->itemViewBtn($item);
        }
        if (auth("gopanel")->user()->can("gopanel.activity.activity-logs.delete")) {
            $view .= $this->itemDeleteBtn($item);
        }
        return '<div class="actions text-center">' . $view . '</div>';
    }

    private function itemViewBtn(Model $item): string
    {
        $url = route("gopanel.activity.activity-logs.view", $this->itemKey($item));
        return ' <a href="' . $url . '" class="btn btn-outline-primary waves-effect waves-light view-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Bax">
                    <i class="fas fa-eye f-20"></i>
                </a> ';
    }

    private function itemDeleteBtn(Model $item): string
    {
        $url = route("gopanel.activity.activity-logs.delete", $this->itemKey($item));
        return '<a class="btn btn-outline-danger waves-effect waves-light delete-btn" data-url="' . $url . '" data-key="' . get_class($item) . '" href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Sil">
                    <i class="fas fa-trash"></i>
                </a> ';
    }
}
