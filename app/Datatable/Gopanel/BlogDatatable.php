<?php

namespace App\Datatable\Gopanel;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Site\Blog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BlogDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(Blog::class, [
            'id' => 'ID',
            'image_view' => 'Şəkil',
            'title' => 'Başlıq',
            'short_description' => 'Qısa məzmun',
            'formatted_date_time' => 'Tarix',
            'views' => 'Baxış',
            'is_active_btn' => 'Status',
            'created_at' => 'Əlavə edilmə tarixi',
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
        return $this->baseQueryScope();
    }

    protected function order(Builder $query, string $columnName, string $columnSort): void
    {
        if (in_array($columnName, ['image_view', 'is_active_btn', 'formatted_date_time'], true)) {
            $query->orderBy('date_time', $columnSort);
            return;
        }

        parent::order($query, $columnName, $columnSort);
    }

    private function itemActions(Model $item): string
    {
        $view = '';

        if (auth('gopanel')->user()->can('gopanel.blog.edit')) {
            $view .= $this->itemEditBtn($item);
        }

        if (auth('gopanel')->user()->can('gopanel.blog.delete')) {
            $view .= $this->itemDeleteBtn($item);
        }

        return '<div class="actions text-center">' . $view . '</div>';
    }

    private function itemEditBtn(Model $item): string
    {
        $url = route('gopanel.blog.store', $this->itemKey($item));

        return ' <a href="' . $url . '" class="btn btn-outline-success waves-effect waves-light" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumata düzəliş et">
                    <i class="fas fa-pen f-20"></i>
                </a> ';
    }

    private function itemDeleteBtn(Model $item): string
    {
        $url = route('gopanel.general.delete', $this->itemKey($item));

        return '<a class="btn btn-outline-danger waves-effect waves-light delete" data-url="' . $url . '" data-key="' . get_class($item) . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumatı sil">
                    <i class="fas fa-trash"></i>
                </a> ';
    }
}
