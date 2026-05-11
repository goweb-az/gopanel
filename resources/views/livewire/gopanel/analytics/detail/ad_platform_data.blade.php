<?php

use App\Livewire\Concerns\WithDatatable;
use App\Models\Analytics\AnalyticsAdPlatformData;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use WithDatatable;

    protected function datatableDefaultSort(): array
    {
        return ['id', 'desc'];
    }

    protected function datatableQuery(): Builder
    {
        return AnalyticsAdPlatformData::query()->with(['click.link', 'platform']);
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

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Reklam platforması məlumatları') }}</h4>
                    <div class="page-title-right">
                        <a wire:navigate href="{{ route('gopanel.analytics.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> {{ __('Analitikə dön') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

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
