<?php

namespace App\Datatable\Gopanel;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Site\Blog;
use App\Models\Site\Slider;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SliderDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(Slider::class, [
            'id'                    => 'ID',
            'image_view'            => 'Şəkil',
            'title'                 => 'Başlıq',
            'description'           => 'Məlumat',
            'created_at'            => 'Əlavə edilmə tarixi'
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
        if (auth("gopanel")->user()->can("gopanel.slider.edit")) {
            $view .= $this->itemEditBtn($item);
        }
        if (auth("gopanel")->user()->can("gopanel.slider.delete")) {
            $view .= $this->itemDeleteBtn($item);
        }
        return '<div class="actions text-center">' . $view . '</div>';
    }

    private function itemEditBtn(Model $item): string
    {
        $url    = route("gopanel.slider.get.form", $item);
        return ' <a href="' . $url . '" class="btn btn-outline-success waves-effect waves-light edit" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumata düzəliş et"> 
                    <i class="fas fa-pen f-20"></i> 
                </a> ';
    }

    private function itemDeleteBtn(Model $item)
    {
        $url        = route("gopanel.general.delete", $item);
        return '<a  class="btn btn-outline-danger waves-effect waves-light delete" data-url="' . $url . '" data-key="' . get_class($item)  . '"" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumatı sil">
                    <i class="fas fa-trash"></i>
                </a> ';
    }
}
