<?php

use App\Livewire\Concerns\WithDatatable;
use App\Models\Analytics\AnalyticsLink;
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
        return AnalyticsLink::query();
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',                'label' => '#',                 'sortable' => true,  'width' => '60px'],
            ['key' => 'locale',            'label' => __('Dil'),           'sortable' => true,  'width' => '80px'],
            ['key' => 'url',               'label' => __('Link'),          'sortable' => true,  'searchable' => true],
            ['key' => 'slug',              'label' => 'Slug',              'sortable' => true,  'searchable' => true],
            ['key' => 'hit_count',         'label' => __('Toplam Giriş'),  'sortable' => true,  'width' => '140px'],
            ['key' => 'first_visited_at',  'label' => __('İlk giriş'),     'sortable' => true,  'width' => '160px'],
            ['key' => 'last_visited_at',   'label' => __('Son giriş'),     'sortable' => true,  'width' => '160px'],
        ];
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Linklər üzrə statistika') }}</h4>
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
                <tr wire:key="link-{{ $record->id }}">
                    <td>{{ $record->id }}</td>
                    <td><code>{{ $record->locale }}</code></td>
                    <td><a href="{{ $record->url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($record->url, 80) }}</a></td>
                    <td><code>{{ $record->slug }}</code></td>
                    <td>{{ number_format($record->hit_count) }}</td>
                    <td>{{ $record->first_visited_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $record->last_visited_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </x-gopanel.datatable>
    </div>
</div>
