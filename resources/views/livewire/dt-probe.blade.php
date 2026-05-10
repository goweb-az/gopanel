<?php

use App\Livewire\Concerns\WithDatatable;
use App\Models\Geography\Language;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use WithDatatable;

    protected function datatableQuery(): Builder
    {
        return Language::query();
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',         'label' => '#',         'sortable' => true,  'width' => '60px'],
            ['key' => 'name',       'label' => 'Adı',       'sortable' => true,  'searchable' => true],
            ['key' => 'code',       'label' => 'Kod',       'sortable' => true,  'searchable' => true,  'width' => '90px'],
            ['key' => 'sort_order', 'label' => 'Sıra',      'sortable' => true,  'width' => '90px'],
            ['key' => 'default',    'label' => 'Default',   'sortable' => true,  'width' => '90px',  'align' => 'center'],
            ['key' => 'is_active',  'label' => 'Status',    'sortable' => true,  'width' => '90px',  'align' => 'center'],
            ['key' => 'created_at', 'label' => 'Yaradılma', 'sortable' => true,  'width' => '160px'],
        ];
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="page-title-box">
            <h4 class="mb-0">Datatable Probe — Languages</h4>
            <p class="text-muted small mb-0">
                Sort by clicking headers, search by name/code, change page size, paginate.
            </p>
        </div>

        <div class="card">
            <div class="card-body">
                <x-gopanel.datatable
                    :rows="$this->rows"
                    :columns="$this->columns"
                    :sortField="$sortField"
                    :sortDirection="$sortDirection"
                    :perPage="$perPage"
                >
                    @foreach ($this->rows as $row)
                        <tr wire:key="dt-{{ $row->id }}">
                            <td>{{ $row->id }}</td>
                            <td><strong>{{ $row->name }}</strong></td>
                            <td><code>{{ $row->upper_code }}</code></td>
                            <td>{{ $row->sort_order }}</td>
                            <td class="text-center">
                                <span class="badge {{ $row->default ? 'bg-primary' : 'bg-light text-muted' }}">
                                    {{ $row->default ? 'Bəli' : 'Xeyr' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $row->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $row->is_active ? 'Aktiv' : 'Deaktiv' }}
                                </span>
                            </td>
                            <td>{{ $row->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </x-gopanel.datatable>
            </div>
        </div>
    </div>
</div>
