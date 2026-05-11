<?php

use App\Livewire\Concerns\WithDatatable;
use App\Models\Analytics\AnalyticsUtmParameter;
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
        return AnalyticsUtmParameter::query()->with('click.link');
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',           'label' => '#',           'sortable' => true,  'width' => '70px'],
            ['key' => 'click',        'label' => __('Klik')],
            ['key' => 'utm_source',   'label' => 'utm_source',  'sortable' => true,  'searchable' => true],
            ['key' => 'utm_medium',   'label' => 'utm_medium',  'sortable' => true,  'searchable' => true],
            ['key' => 'utm_campaign', 'label' => 'utm_campaign','sortable' => true,  'searchable' => true],
            ['key' => 'utm_term',     'label' => 'utm_term',    'sortable' => true],
            ['key' => 'utm_content',  'label' => 'utm_content', 'sortable' => true],
        ];
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <x-gopanel.page-header :title="__('UTM parametrləri')" :showCreateButton="false">
            <x-slot:actions>
                <a wire:navigate href="{{ route('gopanel.analytics.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> {{ __('Analitikə dön') }}
                </a>
            </x-slot:actions>
        </x-gopanel.page-header>

        <x-gopanel.datatable
            :rows="$this->rows"
            :columns="$this->columns"
            :sortField="$sortField"
            :sortDirection="$sortDirection"
            :perPage="$perPage"
        >
            @foreach ($this->rows as $record)
                <tr wire:key="utm-{{ $record->id }}">
                    <td>{{ $record->id }}</td>
                    <td><small>{{ \Illuminate\Support\Str::limit($record->click?->link?->url, 50) ?? '—' }}</small></td>
                    <td>{{ $record->utm_source }}</td>
                    <td>{{ $record->utm_medium }}</td>
                    <td>{{ $record->utm_campaign }}</td>
                    <td>{{ $record->utm_term }}</td>
                    <td>{{ $record->utm_content }}</td>
                </tr>
            @endforeach
        </x-gopanel.datatable>
    </div>
</div>
