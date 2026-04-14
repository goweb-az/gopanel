<?php

namespace App\Datatable\Gopanel\Seo;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Seo\SiteRedirect;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SiteRedirectDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(SiteRedirect::class, [
            'id'                => 'ID',
            'source'            => 'Mənbə',
            'target'            => 'Hədəf',
            'match_type_name'   => 'Uyğunluq',
            'http_code'         => 'HTTP Kodu',
            'is_active_button'  => 'Status',
            'hits'              => 'Hit',
            'priority'          => 'Prioritet',
            'created_at'        => 'Yaradılma tarixi',
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
            $query->where(function ($q) use ($searchInput) {
                $q->whereRaw('LOWER(`source`) LIKE ?', ['%' . $searchInput . '%'])
                  ->orWhereRaw('LOWER(`target`) LIKE ?', ['%' . $searchInput . '%']);
            });
        }

        return $query;
    }

    private function itemActions(Model $item): string
    {
        $view = '';
        if (auth("gopanel")->user()->can("gopanel.seo.site-redirects.edit")) {
            $view .= $this->itemEditBtn($item);
        }
        if (auth("gopanel")->user()->can("gopanel.seo.site-redirects.delete")) {
            $view .= $this->itemDeleteBtn($item);
        }
        return '<div class="actions text-center">' . $view . '</div>';
    }

    private function itemEditBtn(Model $item): string
    {
        $url = route("gopanel.seo.site-redirects.get.form", $this->itemKey($item));
        return ' <a href="' . $url . '" class="btn btn-outline-success waves-effect waves-light edit" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumata düzəliş et">
                    <i class="fas fa-pen f-20"></i>
                </a> ';
    }

    private function itemDeleteBtn(Model $item)
    {
        $url = route("gopanel.general.delete", $this->itemKey($item));
        return '<a href="#" class="btn btn-outline-danger waves-effect waves-light delete" data-url="' . $url . '" data-key="' . get_class($item) . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumatı sil">
                    <i class="fas fa-trash"></i>
                </a> ';
    }
}
