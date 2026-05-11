<?php

use App\Livewire\Concerns\WithAnalyticsDateFilter;
use App\Livewire\Concerns\WithDatatable;
use App\Models\Analytics\AnalyticsCity;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use WithDatatable, WithAnalyticsDateFilter;

    protected function datatableDefaultSort(): array
    {
        return ['hit_count', 'desc'];
    }

    protected function datatableQuery(): Builder
    {
        return $this->applyDateFilter(AnalyticsCity::query()->with('country'), 'last_visited_at');
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',                'label' => '#',                 'sortable' => true,  'width' => '60px'],
            ['key' => 'name',              'label' => __('Şəhər'),         'sortable' => true,  'searchable' => true],
            ['key' => 'country',           'label' => __('Ölkə'),          'width' => '180px'],
            ['key' => 'hit_count',         'label' => __('Toplam Giriş'),  'sortable' => true,  'width' => '140px'],
            ['key' => 'first_visited_at',  'label' => __('İlk giriş'),     'sortable' => true,  'width' => '160px'],
            ['key' => 'last_visited_at',   'label' => __('Son giriş'),     'sortable' => true,  'width' => '160px'],
        ];
    }
}; ?>

<div class="page-content" x-data="{ filterOpen: {{ ($filterDateFrom || $filterDateTo) ? 'true' : 'false' }} }">
    <div class="container-fluid">
        <x-gopanel.page-header :title="__('Şəhərlər üzrə statistika')" :showCreateButton="false">
            <x-slot:actions>
                <x-gopanel.filter-toggle-button />
                <a wire:navigate href="{{ route('gopanel.analytics.index', request()->only(['from', 'to'])) }}" class="btn btn-outline-secondary">
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
                <tr wire:key="city-{{ $record->id }}">
                    <td>{{ $record->id }}</td>
                    <td><strong>{{ $record->name }}</strong></td>
                    <td>
                        @if ($record->country)
                            @if ($record->country->flag)<img src="{{ $record->country->flag }}" alt="" style="width:18px;height:auto;margin-right:6px;">@endif
                            {{ $record->country->name }}
                        @endif
                    </td>
                    <td>{{ number_format($record->hit_count) }}</td>
                    <td>{{ $record->first_visited_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $record->last_visited_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </x-gopanel.datatable>
    </div>
</div>
