<?php

namespace App\Datatable\Gopanel\Activity;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Activity\FileLog;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FilelogsDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(FileLog::class, [
            'id' => 'ID',
            'user_link'     => 'İstifadəçi',
            'admin_link'    => 'Admin',
            'channel_link'  => 'Kanal',
            'level_link'    => 'Səviyyə',
            'message'       => 'Mesaj',
            'created_at'    => 'Tarixi'
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

        if (request()->has("user_id") and request()->user_id >= 0) {
            $query->where('user_id', request()->user_id);
        }

        if (request()->has("admin_id") and request()->admin_id >= 0) {
            $query->where('admin_id', request()->admin_id);
        }

        if (request()->has("channel") and !empty(request()->channel)) {
            $query->where('channel', request()->channel);
        }

        if (request()->has("level") and !empty(request()->level)) {
            $query->where('level', request()->level);
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
                $q->whereRaw('LOWER(channel) LIKE ?', ["%{$searchInput}%"]);
                $q->whereRaw('LOWER(level) LIKE ?', ["%{$searchInput}%"]);
                $q->orWhere('message', 'LIKE', "%{$searchInput}%");
                $q->orWhere('context', 'LIKE', "%{$searchInput}%");
                $q->orWhere('log_details', 'LIKE', "%{$searchInput}%");
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
        $url = route('gopanel.activity.file-logs.show', $item->id);
        return '<a  class="btn btn-outline-primary waves-effect waves-light show-log" data-url="' . $url . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Ətarflı bax">
                    <i class="fas fa-eye"></i>
                </a> ';
    }
}
