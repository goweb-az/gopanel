<?php

namespace App\Datatable\Gopanel\Activity;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Activity\FileLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FileLogsDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(FileLog::class, [
            'id'                   => 'ID',
            'level_badge'          => 'Level',
            'channel'              => 'Kanal',
            'user_name'            => 'İstifadəçi',
            'message_short'        => 'Mesaj',
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
        $query = $this->baseQueryScope();

        // Filtrlar
        if (request('level')) {
            $query->where('level', request('level'));
        }

        if (request('channel')) {
            $query->where('channel', request('channel'));
        }

        if (request('company_id')) {
            $query->where('company_id', request('company_id'));
        }

        if (request('user_id')) {
            $query->where('user_id', request('user_id'));
        }

        if (request('date_from')) {
            $query->where('created_at', '>=', request('date_from') . ' 00:00:00');
        }

        if (request('date_to')) {
            $query->where('created_at', '<=', request('date_to') . ' 23:59:59');
        }

        // Axtarış — yalnız file_logs cədvəlinə
        if ($search = $this->getSearchInput()) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'LIKE', "%{$search}%")
                    ->orWhere('channel', 'LIKE', "%{$search}%")
                    ->orWhere('level', 'LIKE', "%{$search}%");
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
        if (auth("gopanel")->user()->can("gopanel.activity.file-logs.view")) {
            $view .= $this->itemViewBtn($item);
        }
        if (auth("gopanel")->user()->can("gopanel.activity.file-logs.delete")) {
            $view .= $this->itemDeleteBtn($item);
        }
        return '<div class="actions text-center">' . $view . '</div>';
    }

    private function itemViewBtn(Model $item): string
    {
        $url = route("gopanel.activity.file-logs.view", $this->itemKey($item));
        return ' <a href="' . $url . '" class="btn btn-outline-primary waves-effect waves-light view-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Bax">
                    <i class="fas fa-eye f-20"></i>
                </a> ';
    }

    private function itemDeleteBtn(Model $item): string
    {
        $url = route("gopanel.activity.file-logs.delete", $this->itemKey($item));
        return '<a class="btn btn-outline-danger waves-effect waves-light delete-btn" data-url="' . $url . '" data-key="' . get_class($item) . '" href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="Sil">
                    <i class="fas fa-trash"></i>
                </a> ';
    }
}
