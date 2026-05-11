<?php

use App\Livewire\Concerns\WithAnalyticsDateFilter;
use App\Livewire\Concerns\WithDatatable;
use App\Models\Analytics\AnalyticsAdPlatformData;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use WithDatatable, WithAnalyticsDateFilter;

    protected function datatableDefaultSort(): array
    {
        return ['id', 'desc'];
    }

    protected function datatableQuery(): Builder
    {
        return $this->applyDateFilter(AnalyticsAdPlatformData::query()->with(['click.link', 'platform']));
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',          'label' => '#',                       'sortable' => true,  'width' => '70px'],
            ['key' => 'click',       'label' => __('Link')],
            ['key' => 'platform',    'label' => __('Platforma'),           'width' => '180px'],
            ['key' => 'param_key',   'label' => __('Açar (key)'),          'sortable' => true,  'searchable' => true, 'width' => '180px'],
            ['key' => 'param_value', 'label' => __('Dəyər'),               'sortable' => true,  'searchable' => true],
        ];
    }
}; ?>

<div class="page-content" x-data="{ filterOpen: {{ ($filterDateFrom || $filterDateTo) ? 'true' : 'false' }} }">
    <div class="container-fluid">
        <x-gopanel.page-header :title="__('Reklam platforması məlumatları')" :showCreateButton="false">
            <x-slot:actions>
                <x-gopanel.filter-toggle-button />
                <a wire:navigate href="{{ route('gopanel.analytics.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> {{ __('Analitikə dön') }}
                </a>
            </x-slot:actions>
        </x-gopanel.page-header>

        <x-gopanel.analytics-detail-filter />

        <x-gopanel.datatable
            :rows="$this->rows"
            :columns="$this->columns"
            :sortField="$sortField"
            :sortDirection="$sortDirection"
            :perPage="$perPage"
        >
            @foreach ($this->rows as $record)
                <tr wire:key="apd-{{ $record->id }}">
                    <td>{{ $record->id }}</td>
                    <td><small>{{ \Illuminate\Support\Str::limit($record->click?->link?->url, 60) ?? '—' }}</small></td>
                    <td>{{ $record->platform?->name ?? '—' }}</td>
                    <td><code>{{ $record->param_key }}</code></td>
                    <td>{{ $record->param_value }}</td>
                </tr>
            @endforeach
        </x-gopanel.datatable>
    </div>
</div>
