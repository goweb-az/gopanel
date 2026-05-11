<?php

use App\Livewire\Concerns\WithDatatable;
use App\Models\Analytics\AnalyticsDevice;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use WithDatatable;

    protected function datatableDefaultSort(): array
    {
        return ['hit_count', 'desc'];
    }

    protected function datatableQuery(): Builder
    {
        return AnalyticsDevice::query();
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',                'label' => '#',                       'sortable' => true,  'width' => '60px'],
            ['key' => 'device_type',       'label' => __('Cihaz'),               'sortable' => true,  'searchable' => true],
            ['key' => 'icon',              'label' => __('İkon'),                'width' => '80px'],
            ['key' => 'hit_count',         'label' => __('Toplam Giriş'),        'sortable' => true,  'width' => '140px'],
            ['key' => 'first_visited_at',  'label' => __('İlk giriş'),           'sortable' => true,  'width' => '160px'],
            ['key' => 'last_visited_at',   'label' => __('Son giriş'),           'sortable' => true,  'width' => '160px'],
        ];
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Cihazlar üzrə statistika') }}</h4>
                    <div class="page-title-right">
                        <a wire:navigate href="{{ route('gopanel.analytics.index', request()->only(['from', 'to'])) }}" class="btn btn-outline-secondary">
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
                <tr wire:key="device-{{ $record->id }}">
                    <td>{{ $record->id }}</td>
                    <td><strong>{{ $record->device_type }}</strong></td>
                    <td>@if ($record->icon)<i class="{{ $record->icon }} font-size-18"></i>@endif</td>
                    <td>{{ number_format($record->hit_count) }}</td>
                    <td>{{ $record->first_visited_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $record->last_visited_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </x-gopanel.datatable>
    </div>
</div>
