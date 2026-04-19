<?php

namespace App\Datatable\Gopanel\Admins;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Gopanel\CustomRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RoleDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(CustomRole::class, [
            'id' => 'ID',
            'name' => 'Vəzifə adı',
            'permissions_count' => 'İcazələr',
            'assigned_admins_count' => 'Təyinat',
            'created_at' => 'Yaradılma tarixi',
        ], [
            'actions' => [
                'title' => 'Əməliyyatlar',
                'type' => 'callable',
                'view' => function ($item) {
                    return $this->itemActions($item);
                },
            ],
        ]);
    }

    protected function query(): Builder
    {
        return $this->baseQueryScope()->with(['permissions', 'users']);
    }

    protected function order(Builder $query, string $columnName, string $columnSort): void
    {
        if (in_array($columnName, ['permissions_count', 'assigned_admins_count'], true)) {
            $query->orderBy('id', $columnSort);
            return;
        }

        parent::order($query, $columnName, $columnSort);
    }

    private function itemActions(Model $item): string
    {
        $view = '';

        if (auth('gopanel')->user()->can('gopanel.admins.roles.edit')) {
            $view .= $this->itemEditBtn($item);
        }

        if (auth('gopanel')->user()->can('gopanel.admins.roles.delete')) {
            $view .= $this->itemDeleteBtn($item);
        }

        return '<div class="actions text-center">' . $view . '</div>';
    }

    private function itemEditBtn(Model $item): string
    {
        $url = route('gopanel.admins.roles.store', $this->itemKey($item));

        return ' <a href="' . $url . '" class="btn btn-outline-success waves-effect waves-light" data-bs-toggle="tooltip" data-bs-placement="top" title="Vəzifəyə düzəliş et">
                    <i class="fas fa-pen f-20"></i>
                </a> ';
    }

    private function itemDeleteBtn(Model $item): string
    {
        $url = route('gopanel.general.delete', $this->itemKey($item));

        return ' <a href="#" class="btn btn-outline-danger waves-effect waves-light delete" data-url="' . $url . '" data-key="' . get_class($item) . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Vəzifəni sil">
                    <i class="fas fa-trash"></i>
                </a> ';
    }
}
