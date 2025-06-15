<?php

namespace App\Datatable\Gopanel\Translations;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Translations\Translation;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TranslationDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(Translation::class, [
            'id'                    => 'ID',
            'key'                   => 'Açar',
            'editable_value'        => 'Dəyər',
            'platform'              => 'Platforma',
            'locale'                => 'Dil',
            'lang_check_exists'     => 'Mövcudluq',
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
        if ($this->getSearchInput()) {
            $searchInput = strtolower($this->getSearchInput());
            $query->orWhereRaw('LOWER(`key`) LIKE ?', ['%' . $searchInput . '%']);
            $query->orWhereRaw('LOWER(`value`) LIKE ?', ['%' . $searchInput . '%']);
        }


        if (request()->has("platform")) {
            $query->where('platform', request()->input("platform"));
        }

        if (request()->has("locale")) {
            $query->where('locale', request()->input("locale"));
        } else {
            $query->where('locale', app()->getLocale());
        }

        return $query;
    }


    private function itemActions(Model $item): string
    {
        $view = '';
        if (auth("gopanel")->user()->can("gopanel.translations.edit")) {
            $view .= $this->itemEditBtn($item);
        }
        if (auth("gopanel")->user()->can("gopanel.translations.delete")) {
            $view .= $this->itemDeleteBtn($item);
        }
        return '<div class="actions text-center">' . $view . '</div>';
    }

    private function itemEditBtn(Model $item): string
    {
        $url    = route("gopanel.translations.get.form", $item);
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
