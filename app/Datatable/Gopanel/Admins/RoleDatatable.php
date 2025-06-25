<?php

namespace App\Datatable\Gopanel\Admins;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Gopanel\CustomRole;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RoleDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(CustomRole::class, [
            'id'                => 'ID',
            'name'              => 'Name',
            'permissions_count' => 'İcazələri',
            'created_at'        => 'Qeydiyyat tarixi'
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
        $query  = $this->baseQueryScope();

        return $query;
    }


    private function itemActions(Model $item): string
    {
        $view = '';
        if (auth("gopanel")->user()->can("gopanel.admins.edit")) {
            $view .= $this->itemEditBtn($item);
        }
        if (auth("gopanel")->user()->can("gopanel.admins.delete") && $item->is_super == 0) {
            $view .= $this->itemDeleteBtn($item);
        }
        return '<div class="actions text-center">' . $view . '</div>';
    }

    private function itemEditBtn(Model $item): string
    {
        return ' <a href="' . route("gopanel.admins.roles.store", $item) . '" class="btn btn-outline-success waves-effect waves-light" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumata düzəliş et"> 
                    <i class="fas fa-pen f-20"></i> 
                </a> ';
    }

    private function itemDeleteBtn(Model $item)
    {
        return ' <a href="#" class="btn btn-outline-danger waves-effect waves-light delete" data-url="" data-key="' . $item->getTable() . '"" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumatı sil"> 
                    <i class="fas fa-trash"></i> 
                </a> ';
    }
}
