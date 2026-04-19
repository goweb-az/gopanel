<?php

namespace App\Datatable\Gopanel\Admins;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Gopanel\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(Admin::class, [
            'id' => 'ID',
            'full_name' => 'Ad soyad',
            'email' => 'Elektron poct',
            'role_summary' => 'Vəzifə',
            'permission_summary' => 'İcazələr',
            'is_active_btn' => 'Status',
            'is_super_btn' => 'Super Admin',
            'created_at' => 'Qeydiyyat tarixi',
        ], [
            'actions' => [
                'title' => 'Emeliyyatlar',
                'type' => 'callable',
                'view' => function ($item) {
                    return $this->itemActions($item);
                },
            ],
        ]);
    }

    protected function query(): Builder
    {
        return $this->baseQueryScope()
            ->with(['roles.permissions', 'permissions']);
    }

    protected function order(Builder $query, string $columnName, string $columnSort): void
    {
        if (in_array($columnName, ['role_summary', 'permission_summary', 'is_active_btn', 'is_super_btn'], true)) {
            $query->orderBy('id', $columnSort);
            return;
        }

        parent::order($query, $columnName, $columnSort);
    }

    private function itemActions(Model $item): string
    {
        $view = '';

        if (auth('gopanel')->user()->can('gopanel.admins.edit')) {
            $view .= $this->itemEditBtn($item);
        }

        if (auth('gopanel')->user()->can('gopanel.admins.delete') && $item->is_super == 0) {
            $view .= $this->itemDeleteBtn($item);
        }

        return '<div class="actions text-center">' . $view . '</div>';
    }

    private function itemEditBtn(Model $item): string
    {
        $url = route('gopanel.admins.get.form', $this->itemKey($item));

        return ' <a href="' . $url . '" class="btn btn-outline-success waves-effect waves-light edit" data-bs-toggle="tooltip" data-bs-placement="top" title="Melumata duzelis et">
                    <i class="fas fa-pen f-20"></i>
                </a> ';
    }

    private function itemDeleteBtn(Model $item): string
    {
        $route = route('gopanel.general.delete', $this->itemKey($item));

        return ' <a href="' . $route . '" class="btn btn-outline-danger waves-effect waves-light delete" data-url="' . $route . '" data-key="' . get_class($item) . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Melumati sil">
                    <i class="fas fa-trash"></i>
                </a> ';
    }
}
