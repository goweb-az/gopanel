<?php

namespace App\Datatable\Gopanel\Analytics;

use App\Datatable\Gopanel\GopanelDatatable;
use App\Models\Analytics\AnalyticsClick;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClicksDatatable extends GopanelDatatable
{
    public function __construct()
    {
        parent::__construct(AnalyticsClick::class, [
            'id'                    => 'ID',
            'url_link'              => 'Url',
            'referer_link'          => 'Referer',
            'ip_address_click'      => 'IP ünvanı',
            'device_link'           => 'Cihaz',
            'operating_link'        => 'Əməliyyat Sistemi',
            'browser_link'          => 'Brauzer',
            'country_link'          => 'Ölkə',
            'city_link'             => 'Şəhər',
            'language_link'         => 'Dil',
            'created_at'            => 'Giriş tarixi',
        ], [
            // 
        ]);
    }


    protected function query(): Builder
    {
        $query  = $this->baseQueryScope();
        $request = request();
        if ($request->has("device_id") && !empty($request->device_id)) {
            $query->where("device_id", $request->device_id);
        }
        if ($request->has("os_id") && !empty($request->os_id)) {
            $query->where("os_id", $request->os_id);
        }
        if ($request->has("browser_id") && !empty($request->browser_id)) {
            $query->where("browser_id", $request->browser_id);
        }
        if ($request->has("country_id") && !empty($request->country_id)) {
            $query->where("country_id", $request->country_id);
        }
        if ($request->has("city_id") && !empty($request->city_id)) {
            $query->where("city_id", $request->city_id);
        }
        if ($request->has("language_id")) {
            $query->where("language_id", $request->language_id);
        }

        if ($request->has("from") && !empty($request->from)) {
            $from = $request->from . " 00:00:00";
        }

        if ($request->has("to") && !empty($request->to)) {
            $to = $request->to . " 23:59:59";
        }

        if (!empty($from) && !empty($to)) {
            $query->whereBetween("created_at", [$from, $to]);
        } elseif (!empty($from)) {
            $query->where("created_at", ">=", $from);
        } elseif (!empty($to)) {
            $query->where("created_at", "<=", $to);
        }

        if ($this->getSearchInput()) {
            $searchInput = strtolower($this->getSearchInput());
            $query->where(function ($q) use ($searchInput) {
                $q->whereRaw('LOWER(ip_address) LIKE ?', ["%{$searchInput}%"])
                    ->orWhereRaw('LOWER(url) LIKE ?', ["%{$searchInput}%"])
                    ->orWhereRaw('LOWER(referer) LIKE ?', ["%{$searchInput}%"])
                    ->orWhereRaw('LOWER(created_at) LIKE ?', ["%{$searchInput}%"]);
            });
        }
        return $query;
    }


    private function itemActions(Model $item): string
    {
        return '';
    }
}
